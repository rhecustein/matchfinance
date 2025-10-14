<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Plan;
use App\Models\CompanySubscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * CompanySubscriptionController
 * 
 * Mengelola subscription company untuk company users
 * 
 * Access Levels:
 * - View (index, plans, billingHistory): ALL company members
 * - Actions (changePlan, cancel, resume): Owner ONLY (via company.owner middleware)
 * 
 * Features:
 * - View current subscription & plan details
 * - Browse available plans
 * - View billing history
 * - Change plan (Owner only)
 * - Cancel subscription (Owner only)
 * - Resume/Reactivate subscription (Owner only)
 * 
 * Security:
 * - Super Admin akan di-redirect ke admin panel
 * - User hanya bisa akses subscription company mereka sendiri
 * - Tenant isolation via auth()->user()->company
 */
class CompanySubscriptionController extends Controller
{
    /**
     * Display subscription dashboard/overview
     * 
     * Shows current subscription status, plan details, and usage info
     * 
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function index()
    {
        $user = auth()->user();
        
        // Redirect Super Admin ke admin panel
        if ($user->isSuperAdmin()) {
            return redirect()->route('admin.subscriptions.index')
                ->with('info', 'Super Admin menggunakan panel admin untuk mengelola subscription.');
        }

        // User harus punya company
        abort_unless($user->company_id, 403, 'Anda harus tergabung dalam company untuk mengakses halaman ini.');

        // Load company dengan subscription & plan
        $company = $user->company()
            ->with([
                'activeSubscription.plan',
                'owner'
            ])
            ->first();

        $subscription = $company->activeSubscription;
        
        // Subscription status checks
        $hasActiveSubscription = $company->hasActiveSubscription();
        $isTrial = $company->isTrial();
        $trialExpired = $company->isTrialExpired();
        
        // Trial info
        $trialDaysRemaining = null;
        if ($isTrial && $company->trial_ends_at) {
            $trialDaysRemaining = now()->diffInDays($company->trial_ends_at, false);
            $trialDaysRemaining = max(0, (int) $trialDaysRemaining);
        }

        // Subscription expiry info
        $daysUntilExpiry = null;
        $needsRenewal = false;
        $isExpiringSoon = false;
        
        if ($subscription && $subscription->ends_at) {
            $daysUntilExpiry = now()->diffInDays($subscription->ends_at, false);
            $daysUntilExpiry = (int) $daysUntilExpiry;
            
            // Check if needs renewal (within 30 days)
            $needsRenewal = $subscription->needsRenewal(30);
            
            // Check if expiring soon (within 7 days)
            $isExpiringSoon = $subscription->needsRenewal(7);
        }

        // Current plan info
        $currentPlan = $subscription?->plan;

        // Company usage statistics (if you have these fields)
        $usageStats = [
            'users_count' => $company->users()->count(),
            'max_users' => $currentPlan?->getFeature('max_users', 0),
            
            'statements_count' => $company->bankStatements()->count(),
            'max_statements' => $currentPlan?->getFeature('max_bank_statements', 0),
            
            // Add more usage stats as needed
        ];

        // Check if user is owner (untuk show/hide action buttons di view)
        $isOwner = $user->isOwner();

        return view('subscription.index', compact(
            'company',
            'subscription',
            'hasActiveSubscription',
            'isTrial',
            'trialExpired',
            'trialDaysRemaining',
            'daysUntilExpiry',
            'needsRenewal',
            'isExpiringSoon',
            'currentPlan',
            'usageStats',
            'isOwner'
        ));
    }

    /**
     * Display available subscription plans
     * 
     * Shows all active plans with comparison to current plan
     * 
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function plans()
    {
        $user = auth()->user();
        
        // Redirect Super Admin
        if ($user->isSuperAdmin()) {
            return redirect()->route('admin.plans.index');
        }

        abort_unless($user->company_id, 403, 'Anda harus tergabung dalam company.');

        // Load company dengan current subscription
        $company = $user->company()
            ->with('activeSubscription.plan')
            ->first();

        $currentSubscription = $company->activeSubscription;
        $currentPlan = $currentSubscription?->plan;
        $currentPlanId = $currentPlan?->id;

        // Get all active plans, grouped by billing period
        $allPlans = Plan::active()
            ->orderBy('price', 'asc')
            ->get();

        $monthlyPlans = $allPlans->where('billing_period', 'monthly');
        $yearlyPlans = $allPlans->where('billing_period', 'yearly');

        // Calculate savings for yearly plans
        foreach ($yearlyPlans as $plan) {
            $plan->savings_amount = $plan->getSavingsAmount();
            $plan->savings_percentage = $plan->getSavingsPercentage();
        }

        // Company status info
        $companyStatus = [
            'is_trial' => $company->isTrial(),
            'trial_ends_at' => $company->trial_ends_at,
            'can_subscribe' => !$company->hasActiveSubscription() || $company->isTrialExpired(),
        ];

        // Check if user is owner
        $isOwner = $user->isOwner();

        return view('subscription.plans', compact(
            'company',
            'currentSubscription',
            'currentPlan',
            'currentPlanId',
            'monthlyPlans',
            'yearlyPlans',
            'companyStatus',
            'isOwner'
        ));
    }

    /**
     * Display billing history
     * 
     * Shows all past and current subscriptions for the company
     * 
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function billingHistory()
    {
        $user = auth()->user();
        
        // Redirect Super Admin
        if ($user->isSuperAdmin()) {
            return redirect()->route('admin.subscriptions.index');
        }

        abort_unless($user->company_id, 403, 'Anda harus tergabung dalam company.');

        $company = $user->company;

        // Get all subscriptions untuk company ini dengan pagination
        $subscriptions = CompanySubscription::where('company_id', $company->id)
            ->with('plan')
            ->latest('created_at')
            ->paginate(20);

        // Calculate totals
        $totalPaid = CompanySubscription::where('company_id', $company->id)
            ->whereIn('status', ['active', 'cancelled', 'expired'])
            ->get()
            ->sum(function ($subscription) {
                return $subscription->plan?->price ?? 0;
            });

        return view('subscription.billing-history', compact(
            'company',
            'subscriptions',
            'totalPaid'
        ));
    }

    /**
     * Change subscription plan (Upgrade/Downgrade)
     * 
     * Owner Only - via company.owner middleware
     * 
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function changePlan(Request $request)
    {
        $user = auth()->user();
        
        // Double check: User must be owner (middleware juga check)
        abort_unless($user->isOwner(), 403, 'Hanya Owner yang dapat mengubah plan subscription.');
        abort_unless($user->company_id, 403, 'Anda harus tergabung dalam company.');

        // Validate input
        $validated = $request->validate([
            'plan_id' => 'required|exists:plans,id',
        ]);

        $company = $user->company()->with('activeSubscription.plan')->first();
        $subscription = $company->activeSubscription;

        // Validasi: Harus punya subscription aktif
        if (!$subscription) {
            return back()->with('error', 'Company Anda tidak memiliki subscription aktif. Silakan subscribe terlebih dahulu.');
        }

        // Get new plan
        $newPlan = Plan::findOrFail($validated['plan_id']);

        // Validasi: Plan harus aktif
        if (!$newPlan->is_active) {
            return back()->with('error', 'Plan yang dipilih tidak tersedia saat ini.');
        }

        // Validasi: Plan harus berbeda
        if ($subscription->plan_id === $newPlan->id) {
            return back()->with('warning', 'Anda sudah menggunakan plan ini.');
        }

        // Store old plan untuk comparison
        $oldPlan = $subscription->plan;
        $isUpgrade = $newPlan->price > $oldPlan->price;
        $priceDifference = $newPlan->price - $oldPlan->price;

        DB::beginTransaction();
        try {
            // Switch plan
            $subscription->switchPlan($newPlan);
            
            // Recalculate end date berdasarkan billing period plan baru
            $subscription->calculateEndDate();

            // Log activity
            Log::info('Subscription plan changed by owner', [
                'subscription_id' => $subscription->id,
                'company_id' => $company->id,
                'company_name' => $company->name,
                'user_id' => $user->id,
                'user_email' => $user->email,
                'old_plan' => [
                    'id' => $oldPlan->id,
                    'name' => $oldPlan->name,
                    'price' => $oldPlan->price,
                    'billing_period' => $oldPlan->billing_period,
                ],
                'new_plan' => [
                    'id' => $newPlan->id,
                    'name' => $newPlan->name,
                    'price' => $newPlan->price,
                    'billing_period' => $newPlan->billing_period,
                ],
                'price_difference' => $priceDifference,
                'is_upgrade' => $isUpgrade,
            ]);

            DB::commit();

            $action = $isUpgrade ? 'upgrade' : 'downgrade';
            $message = "Berhasil {$action} plan dari '{$oldPlan->name}' ke '{$newPlan->name}'.";
            
            if ($isUpgrade) {
                $message .= " Terima kasih atas kepercayaan Anda!";
            }

            return redirect()->route('subscription.index')
                ->with('success', $message);

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Failed to change subscription plan', [
                'subscription_id' => $subscription->id,
                'company_id' => $company->id,
                'user_id' => $user->id,
                'new_plan_id' => $newPlan->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->with('error', 'Gagal mengubah plan: ' . $e->getMessage());
        }
    }

    /**
     * Cancel subscription
     * 
     * Owner Only - via company.owner middleware
     * Subscription akan tetap aktif hingga akhir periode billing
     * 
     * @return \Illuminate\Http\RedirectResponse
     */
    public function cancel()
    {
        $user = auth()->user();
        
        // Double check: User must be owner
        abort_unless($user->isOwner(), 403, 'Hanya Owner yang dapat membatalkan subscription.');
        abort_unless($user->company_id, 403, 'Anda harus tergabung dalam company.');

        $company = $user->company()->with('activeSubscription.plan')->first();
        $subscription = $company->activeSubscription;

        // Validasi: Harus punya subscription aktif
        if (!$subscription) {
            return back()->with('error', 'Tidak ada subscription aktif yang dapat dibatalkan.');
        }

        // Validasi: Check apakah subscription bisa di-cancel
        if (!$subscription->canBeCancelled()) {
            return back()->with('error', 'Subscription ini tidak dapat dibatalkan. Status saat ini: ' . ucfirst($subscription->status));
        }

        DB::beginTransaction();
        try {
            // Store info untuk display
            $planName = $subscription->plan->name;
            $endsAt = $subscription->ends_at;

            // Cancel subscription
            // Method cancel() akan set:
            // - status = 'cancelled'
            // - cancelled_at = now()
            $subscription->cancel();

            // Optional: Update company status
            // Note: Company masih bisa digunakan sampai ends_at
            // $company->update(['status' => 'cancelled']);

            // Log activity
            Log::warning('Subscription cancelled by owner', [
                'subscription_id' => $subscription->id,
                'company_id' => $company->id,
                'company_name' => $company->name,
                'plan_name' => $planName,
                'ends_at' => $endsAt,
                'user_id' => $user->id,
                'user_email' => $user->email,
                'cancelled_at' => now(),
            ]);

            DB::commit();

            $message = "Subscription '{$planName}' berhasil dibatalkan.";
            if ($endsAt) {
                $message .= " Layanan akan tetap aktif hingga " . $endsAt->format('d M Y') . ".";
            }

            return redirect()->route('subscription.index')
                ->with('success', $message);

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Failed to cancel subscription', [
                'subscription_id' => $subscription->id,
                'company_id' => $company->id,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->with('error', 'Gagal membatalkan subscription: ' . $e->getMessage());
        }
    }

    /**
     * Resume/Reactivate cancelled subscription
     * 
     * Owner Only - via company.owner middleware
     * Mengaktifkan kembali subscription yang sudah di-cancel
     * 
     * @return \Illuminate\Http\RedirectResponse
     */
    public function resume()
    {
        $user = auth()->user();
        
        // Double check: User must be owner
        abort_unless($user->isOwner(), 403, 'Hanya Owner yang dapat mengaktifkan kembali subscription.');
        abort_unless($user->company_id, 403, 'Anda harus tergabung dalam company.');

        $company = $user->company()->with('activeSubscription.plan')->first();
        $subscription = $company->activeSubscription;

        // Validasi: Harus punya subscription
        if (!$subscription) {
            return back()->with('error', 'Tidak ada subscription yang dapat diaktifkan kembali.');
        }

        // Validasi: Subscription harus dalam status cancelled atau expired
        if (!in_array($subscription->status, ['cancelled', 'expired'])) {
            return back()->with('warning', 'Subscription Anda sudah aktif atau tidak dapat diaktifkan kembali.');
        }

        // Check apakah subscription bisa di-reactivate
        if (!$subscription->canBeReactivated()) {
            return back()->with('error', 'Subscription ini tidak dapat diaktifkan kembali. Silakan pilih plan baru.');
        }

        DB::beginTransaction();
        try {
            // Reactivate subscription
            // Method activate() akan set:
            // - status = 'active'
            // - cancelled_at = null
            $subscription->activate();

            // Jika subscription sudah expired, perpanjang
            if ($subscription->ends_at && $subscription->ends_at->isPast()) {
                $subscription->renew();
            }

            // Update company status
            $company->update(['status' => 'active']);

            // Log activity
            Log::info('Subscription reactivated by owner', [
                'subscription_id' => $subscription->id,
                'company_id' => $company->id,
                'company_name' => $company->name,
                'plan_name' => $subscription->plan->name,
                'new_status' => $subscription->status,
                'ends_at' => $subscription->ends_at,
                'user_id' => $user->id,
                'user_email' => $user->email,
            ]);

            DB::commit();

            $message = "Subscription berhasil diaktifkan kembali!";
            if ($subscription->ends_at) {
                $message .= " Aktif hingga " . $subscription->ends_at->format('d M Y') . ".";
            }

            return redirect()->route('subscription.index')
                ->with('success', $message);

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Failed to resume subscription', [
                'subscription_id' => $subscription->id,
                'company_id' => $company->id,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->with('error', 'Gagal mengaktifkan kembali subscription: ' . $e->getMessage());
        }
    }
}