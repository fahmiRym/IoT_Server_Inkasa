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
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $user->update([
            'otp_code'       => $code,
            'otp_expires_at' => now()->addMinutes(5),
        ]);

        Mail::to($user->email)->send(new OtpMail($code, $user->name));
    }

    private function maskEmail(string $email): string
    {
        if (!str_contains($email, '@')) return '****';
        [$local, $domain] = explode('@', $email, 2);
        $masked = substr($local, 0, 2) . str_repeat('*', max(2, strlen($local) - 2));
        return $masked . '@' . $domain;
    }
}
