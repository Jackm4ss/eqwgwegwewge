<?php

namespace App\Http\Controllers\Staff\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Staff\StaffLoginRequest;
use App\Models\StaffUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthenticatedStaffSessionController extends Controller
{
    public function create(): \Illuminate\Contracts\View\View|RedirectResponse
    {
        if (Auth::guard('staff')->check()) {
            return redirect()->route('staff.home');
        }

        return view('staff.login');
    }

    public function store(StaffLoginRequest $request): RedirectResponse
    {
        $credentials = [
            'email' => (string) $request->input('email'),
            'password' => (string) $request->input('password'),
            'is_active' => true,
        ];

        if (! Auth::guard('staff')->attempt($credentials, $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'email' => 'The provided credentials do not match our records.',
            ]);
        }

        $request->session()->regenerate();
        $request->session()->forget('staff_station_id');

        /** @var StaffUser $staff */
        $staff = Auth::guard('staff')->user();
        $staff->forceFill(['last_login_at' => now()])->save();

        return redirect()->intended(route('staff.station.create'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('staff')->logout();

        $request->session()->forget('staff_station_id');
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('staff.login');
    }
}
