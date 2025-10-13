<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        // Get authenticated user
        $user = Auth::user();

        // Set current tenant if user has company
        if ($user->company_id) {
            setCurrentTenant($user->company_id);
        }

        // Redirect based on user role
        return $this->redirectBasedOnRole($user);
    }

    /**
     * Redirect user based on their role
     */
    protected function redirectBasedOnRole($user): RedirectResponse
    {
        // Super Admin - redirect to admin dashboard
        if ($user->isSuperAdmin()) {
            return redirect()->intended(route('admin.dashboard'));
        }

        // Company Owner - redirect to company dashboard
        if ($user->isOwner()) {
            return redirect()->intended(route('dashboard'));
        }

        // Admin - redirect to dashboard
        if ($user->isAdmin()) {
            return redirect()->intended(route('dashboard'));
        }

        // Manager - redirect to dashboard
        if ($user->isManager()) {
            return redirect()->intended(route('dashboard'));
        }

        // Staff or default - redirect to dashboard
        return redirect()->intended(route('dashboard'));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        // Clear tenant data
        clearCurrentTenant();

        return redirect('/');
    }
}