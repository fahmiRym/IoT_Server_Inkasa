<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DeviceController extends Controller
{
    /**
     * List semua session aktif user login (device management).
     */
    public function index(Request $request)
    {
        if (config('session.driver') !== 'database') {
            return view('devices.index', [
                'sessions'    => collect(),
                'currentId'   => null,
                'unsupported' => true,
            ]);
        }

        $currentId = $request->session()->getId();
        $sessions = DB::table('sessions')
            ->where('user_id', Auth::id())
            ->orderByDesc('last_activity')
            ->get()
            ->map(function ($s) use ($currentId) {
                $s->is_current = $s->id === $currentId;
                $s->last_seen  = \Carbon\Carbon::createFromTimestamp($s->last_activity);
                $s->device     = $this->parseAgent($s->user_agent);
                return $s;
            });

        return view('devices.index', [
            'sessions'    => $sessions,
            'currentId'   => $currentId,
            'unsupported' => false,
        ]);
    }

    /**
     * Logout dari 1 session (device) spesifik — bukan session sendiri.
     */
    public function destroy(Request $request, string $id)
    {
        if (config('session.driver') !== 'database') {
            return back()->with('error', 'Fitur ini butuh SESSION_DRIVER=database.');
        }

        if ($id === $request->session()->getId()) {
            return back()->with('error', 'Tidak bisa logout session Anda sendiri dari sini. Gunakan tombol Logout.');
        }

        $deleted = DB::table('sessions')
            ->where('user_id', Auth::id())
            ->where('id', $id)
            ->delete();

        if ($deleted === 0) {
            return back()->with('error', 'Session tidak ditemukan atau bukan milik Anda.');
        }

        return back()->with('success', 'Device berhasil di-logout.');
    }

    /**
     * Logout dari SEMUA device lain (kecuali session current).
     */
    public function destroyOthers(Request $request)
    {
        if (config('session.driver') !== 'database') {
            return back()->with('error', 'Fitur ini butuh SESSION_DRIVER=database.');
        }

        $currentId = $request->session()->getId();
        $deleted = DB::table('sessions')
            ->where('user_id', Auth::id())
            ->where('id', '!=', $currentId)
            ->delete();

        return back()->with('success', "Berhasil logout dari {$deleted} device lain.");
    }

    /**
     * Parse user-agent jadi info device singkat.
     */
    private function parseAgent(?string $ua): array
    {
        $ua = $ua ?? '';
        $device = 'Desktop';
        $icon = 'fa-desktop';
        if (preg_match('/iPhone|iPod/i', $ua)) { $device = 'iPhone'; $icon = 'fa-mobile-screen'; }
        elseif (preg_match('/iPad/i', $ua))    { $device = 'iPad';   $icon = 'fa-tablet-screen-button'; }
        elseif (preg_match('/Android.*Mobile/i', $ua)) { $device = 'Android Phone'; $icon = 'fa-mobile-screen-button'; }
        elseif (preg_match('/Android/i', $ua)) { $device = 'Android Tablet'; $icon = 'fa-tablet-screen-button'; }
        elseif (preg_match('/Macintosh/i', $ua)) { $device = 'Mac'; $icon = 'fa-laptop'; }
        elseif (preg_match('/Windows/i', $ua)) { $device = 'Windows PC'; $icon = 'fa-desktop'; }
        elseif (preg_match('/Linux/i', $ua))   { $device = 'Linux'; $icon = 'fa-desktop'; }

        $browser = 'Browser';
        if (preg_match('/Edg\//i', $ua))            $browser = 'Edge';
        elseif (preg_match('/OPR\//i', $ua))        $browser = 'Opera';
        elseif (preg_match('/Chrome\//i', $ua))     $browser = 'Chrome';
        elseif (preg_match('/Firefox\//i', $ua))    $browser = 'Firefox';
        elseif (preg_match('/Safari\//i', $ua))     $browser = 'Safari';

        return ['device' => $device, 'icon' => $icon, 'browser' => $browser];
    }
}
