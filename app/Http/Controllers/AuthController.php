<?php

namespace App\Http\Controllers;

use App\Mail\OtpMail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /* ──────────────────────────────────────────
     |  LOGIN
     ────────────────────────────────────────── */

    public function showLogin()
    {
        if (Auth::check()) {
            return redirect('/');
        }
        return view('auth.login');
    }

    public function login(Request $request)
    {
        // Bot guard: reject kalau User-Agent kosong (browser real selalu kirim UA).
        // Ini blok auto-script/monitoring tool yang hit /login tiap N detik tanpa UA.
        $ua = trim((string) $request->userAgent());
        if ($ua === '' || $ua === '-') {
            \Illuminate\Support\Facades\Log::warning('Login blocked: empty User-Agent', [
                'ip' => $request->ip(),
            ]);
            return response()->json(['error' => 'invalid request'], 400);
        }

        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $login = strtolower(trim($request->input('username')));

        // Terima username (mis. "fahmi") atau email penuh
        $credentials = filter_var($login, FILTER_VALIDATE_EMAIL)
            ? ['email' => $login, 'password' => $request->input('password')]
            : ['username' => $login, 'password' => $request->input('password')];

        // Cek credentials tanpa login dulu
        $user = User::where(array_key_exists('email', $credentials) ? 'email' : 'username', $login)->first();

        if (!$user || !Hash::check($request->input('password'), $user->password)) {
            throw ValidationException::withMessages([
                'username' => 'Username atau password salah.',
            ]);
        }

        // Jika superadmin → wajib OTP
        if ($user->isSuperAdmin()) {
            $this->generateAndSendOtp($user);

            // Simpan pending user di session (belum login)
            $request->session()->put('otp_user_id', $user->id);
            $request->session()->put('otp_remember', $request->boolean('remember'));

            return redirect()->route('otp.show');
        }

        // Admin → login langsung
        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();
        return redirect()->intended('/');
    }

    /* ──────────────────────────────────────────
     |  OTP
     ────────────────────────────────────────── */

    public function showOtp(Request $request)
    {
        if (!$request->session()->has('otp_user_id')) {
            return redirect()->route('login');
        }

        $user = User::find($request->session()->get('otp_user_id'));
        if (!$user) {
            return redirect()->route('login');
        }

        // Sensor berapa detik sisa berlaku OTP
        $secondsLeft = $user->otp_expires_at
            ? max(0, now()->diffInSeconds($user->otp_expires_at, false))
            : 0;

        return view('auth.otp', [
            'maskedEmail'  => $this->maskEmail($user->email ?? ''),
            'secondsLeft'  => (int) $secondsLeft,
        ]);
    }

    public function verifyOtp(Request $request)
    {
        $request->validate([
            'otp' => 'required|string|size:6',
        ]);

        if (!$request->session()->has('otp_user_id')) {
            return redirect()->route('login')->withErrors(['otp' => 'Sesi OTP tidak valid.']);
        }

        $user = User::find($request->session()->get('otp_user_id'));

        if (!$user) {
            return redirect()->route('login');
        }

        if (!$user->isOtpValid($request->input('otp'))) {
            return back()->withErrors([
                'otp' => now()->greaterThan($user->otp_expires_at)
                    ? 'Kode OTP sudah kadaluarsa. Klik "Kirim Ulang".'
                    : 'Kode OTP tidak valid.',
            ]);
        }

        // OTP valid → login user
        $remember = $request->session()->pull('otp_remember', false);
        $request->session()->forget('otp_user_id');

        // Bersihkan OTP dari DB
        $user->update(['otp_code' => null, 'otp_expires_at' => null]);

        Auth::login($user, $remember);
        $request->session()->regenerate();

        return redirect()->intended('/');
    }

    public function resendOtp(Request $request)
    {
        if (!$request->session()->has('otp_user_id')) {
            return redirect()->route('login');
        }

        $user = User::find($request->session()->get('otp_user_id'));
        if (!$user) {
            return redirect()->route('login');
        }

        // Rate limit: resend hanya jika OTP terakhir dikirim >60 detik lalu
        if ($user->otp_expires_at && now()->lessThan($user->otp_expires_at->subMinutes(4))) {
            return back()->withErrors(['otp' => 'Tunggu 60 detik sebelum meminta OTP baru.']);
        }

        $this->generateAndSendOtp($user);

        return back()->with('success', 'Kode OTP baru telah dikirim ke email Anda.');
    }

    /* ──────────────────────────────────────────
     |  LOGOUT
     ────────────────────────────────────────── */

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/login');
    }

    /* ──────────────────────────────────────────
     |  PROFILE
     ────────────────────────────────────────── */

    public function showProfile()
    {
        return view('auth.profile', ['user' => Auth::user()]);
    }

    public function updateProfile(Request $request)
    {
        $user = Auth::user();

        $data = $request->validate([
            'name'     => 'required|string|max:100',
            'username' => ['required', 'string', 'min:3', 'max:50', 'regex:/^[a-z0-9_.]+$/', Rule::unique('users', 'username')->ignore($user->id)],
            'email'    => ['nullable', 'email', 'max:150', Rule::unique('users', 'email')->ignore($user->id)],
            'photo'    => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'remove_photo'     => 'nullable|boolean',
            'current_password' => 'nullable|string',
            'new_password'     => 'nullable|string|min:6|confirmed',
        ]);

        // Password
        if (!empty($data['new_password'])) {
            if (empty($data['current_password']) || !Hash::check($data['current_password'], $user->password)) {
                throw ValidationException::withMessages([
                    'current_password' => 'Password lama tidak cocok.',
                ]);
            }
            $user->password = Hash::make($data['new_password']);
        }

        // Foto: hapus foto lama kalau diminta atau diganti
        $removeOld = !empty($data['remove_photo']);
        $uploaded  = $request->file('photo');

        if (($removeOld || $uploaded) && $user->photo) {
            Storage::disk('public')->delete($user->photo);
            $user->photo = null;
        }

        if ($uploaded) {
            $ext  = $uploaded->getClientOriginalExtension() ?: 'jpg';
            $name = sprintf('avatars/%d_%s.%s', $user->id, now()->format('YmdHis'), strtolower($ext));
            $uploaded->storeAs('', $name, 'public');
            $user->photo = $name;
        }

        $user->name     = $data['name'];
        $user->username = strtolower($data['username']);
        $user->email    = $data['email'] ?: null;
        $user->save();

        return back()->with('success', 'Profil berhasil disimpan.');
    }

    /* ──────────────────────────────────────────
     |  HELPERS
     ────────────────────────────────────────── */

    private function generateAndSendOtp(User $user): void
    {
        // ===== DEDUPE: cegah spam OTP =====
        // Kalau user baru generate OTP < 60 detik lalu, skip regenerate.
        // OTP lama masih valid (TTL 5 menit). Browser tetap dapat halaman OTP,
        // tapi tidak kirim notif baru ke Telegram/email.
        $lastSentKey = 'otp.last_sent:' . $user->id;
        $lastSent    = \Illuminate\Support\Facades\Cache::get($lastSentKey);

        if ($lastSent) {
            // Carbon versi baru return signed diff; abs() biar aman untuk window check
            $elapsed = abs((int) now()->diffInSeconds(\Carbon\Carbon::parse($lastSent)));
            // Window 120s — aman dari scheduled attack per-60-detik
            if ($elapsed < 120) {
                \Illuminate\Support\Facades\Log::info(
                    "OTP dedupe: user={$user->username} elapsed={$elapsed}s < 120s, SKIP send."
                );
                return;
            }
        }

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $user->update([
            'otp_code'       => $code,
            'otp_expires_at' => now()->addMinutes(5),
        ]);

        // Catat waktu generate — basis dedupe berikutnya
        \Illuminate\Support\Facades\Cache::put($lastSentKey, now()->toIso8601String(), now()->addMinutes(5));

        // Kirim via email — kalau MAIL_MAILER=log, hanya masuk ke storage/logs
        try {
            Mail::to($user->email)->send(new OtpMail($code, $user->name));
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('OTP mail failed: ' . $e->getMessage());
        }

        // Fallback: kirim ke Telegram bot kalau MAIL_MAILER=log (belum ada SMTP proper)
        // atau selalu (dobel-notif) — atur via env NOC_OTP_TELEGRAM=1
        $mailIsLog = strtolower((string) config('mail.default')) === 'log';
        $forceTele = filter_var(env('NOC_OTP_TELEGRAM', false), FILTER_VALIDATE_BOOLEAN);

        if ($mailIsLog || $forceTele) {
            $this->sendOtpTelegram($user, $code);
        }
    }

    private function sendOtpTelegram(User $user, string $code): void
    {
        $token  = config('services.telegram.bot_token');
        $chatId = config('services.telegram.chat_id');
        if (!$token || !$chatId) return;

        $msg = sprintf(
            "🔐 *NOC OTP LOGIN*\nUser: `%s` (%s)\nKode: `%s`\nBerlaku: 5 menit\nWaktu: %s",
            $user->username ?? '-',
            $user->name,
            $code,
            now()->format('Y-m-d H:i:s')
        );

        try {
            \Illuminate\Support\Facades\Http::timeout(5)->retry(2, 250)
                ->post("https://api.telegram.org/bot{$token}/sendMessage", [
                    'chat_id'    => $chatId,
                    'text'       => $msg,
                    'parse_mode' => 'Markdown',
                ]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('OTP telegram send failed: ' . $e->getMessage());
        }
    }

    private function maskEmail(string $email): string
    {
        if (!str_contains($email, '@')) return '****';
        [$local, $domain] = explode('@', $email, 2);
        $masked = substr($local, 0, 2) . str_repeat('*', max(2, strlen($local) - 2));
        return $masked . '@' . $domain;
    }
}
