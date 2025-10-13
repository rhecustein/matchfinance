<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Str;

class OAuthController extends Controller
{
    /**
     * Redirect to Google
     */
    public function redirectToGoogle()
    {
        return Socialite::driver('google')->redirect();
    }

    /**
     * Handle Google callback
     */
    public function handleGoogleCallback()
    {
        try {
            $googleUser = Socialite::driver('google')->user();
            
            // Find or create user
            $user = User::where('provider', 'google')
                       ->where('provider_id', $googleUser->getId())
                       ->first();

            if (!$user) {
                // Check if email already exists (regular user)
                $existingUser = User::where('email', $googleUser->getEmail())->first();
                
                if ($existingUser) {
                    // Link OAuth to existing account
                    $existingUser->update([
                        'provider' => 'google',
                        'provider_id' => $googleUser->getId(),
                        'provider_token' => $googleUser->token,
                        'provider_refresh_token' => $googleUser->refreshToken,
                        'provider_data' => [
                            'name' => $googleUser->getName(),
                            'email' => $googleUser->getEmail(),
                            'avatar' => $googleUser->getAvatar(),
                        ],
                        'email_verified_at' => now(),
                    ]);
                    
                    $user = $existingUser;
                } else {
                    // Create new user
                    // First, find or create default company
                    $company = $this->getOrCreateDefaultCompany();
                    
                    $user = User::create([
                        'company_id' => $company->id,
                        'name' => $googleUser->getName(),
                        'email' => $googleUser->getEmail(),
                        'email_verified_at' => now(),
                        'password' => null, // No password for OAuth users
                        'provider' => 'google',
                        'provider_id' => $googleUser->getId(),
                        'provider_token' => $googleUser->token,
                        'provider_refresh_token' => $googleUser->refreshToken,
                        'provider_data' => [
                            'name' => $googleUser->getName(),
                            'email' => $googleUser->getEmail(),
                            'avatar' => $googleUser->getAvatar(),
                        ],
                        'avatar' => $googleUser->getAvatar(),
                        'role' => 'user',
                        'is_active' => true,
                    ]);
                }
            } else {
                // Update tokens
                $user->updateOAuthTokens(
                    $googleUser->token,
                    $googleUser->refreshToken
                );
                
                // Update avatar if changed
                if ($googleUser->getAvatar() !== $user->avatar) {
                    $user->update(['avatar' => $googleUser->getAvatar()]);
                }
            }

            // Check if account is active
            if (!$user->isActive()) {
                return redirect()->route('login')
                    ->withErrors(['email' => 'Your account has been suspended.']);
            }

            // Check if account is locked
            if ($user->isLocked()) {
                return redirect()->route('login')
                    ->withErrors(['email' => 'Account is locked due to failed login attempts. Try again later.']);
            }

            // Login user
            Auth::login($user, true);
            $user->recordLogin();

            return redirect()->intended(route('dashboard'))
                ->with('success', 'Welcome back, ' . $user->name . '!');

        } catch (\Exception $e) {
            return redirect()->route('login')
                ->withErrors(['email' => 'Unable to login with Google. Please try again.']);
        }
    }

    /**
     * Get or create default company for OAuth users
     */
    private function getOrCreateDefaultCompany()
    {
        // Check if there's a default company
        $company = \App\Models\Company::where('slug', 'default')->first();
        
        if (!$company) {
            $company = \App\Models\Company::create([
                'name' => 'Default Company',
                'slug' => 'default',
                'subdomain' => 'default',
                'status' => 'active',
            ]);
        }
        
        return $company;
    }
}