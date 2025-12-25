<?php

namespace App\Services;

use App\Models\GdprRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Lunar\Models\Customer;
use Lunar\Models\Order;
use Lunar\Models\Address;

class GdprDataDeletionService
{
    /**
     * Delete all user data
     */
    public function deleteUserData(User $user, GdprRequest $request): void
    {
        DB::transaction(function () use ($user, $request) {
            try {
                $request->addLog('Starting data deletion process', ['user_id' => $user->id]);

                // Get customer before deletion
                $customer = $user->customers()->first();

                // Delete user-related data
                $this->deleteUserRelatedData($user, $request);

                // Delete customer data if exists
                if ($customer) {
                    $this->deleteCustomerData($customer, $request);
                }

                // Delete the user account
                $user->delete();
                $request->addLog('User account deleted', ['user_id' => $user->id]);

                $request->markAsCompleted();
            } catch (\Exception $e) {
                $request->addLog('Error during deletion', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                $request->update(['status' => GdprRequest::STATUS_FAILED]);
                throw $e;
            }
        });
    }

    /**
     * Delete customer data (when no user account exists)
     */
    public function deleteCustomerData(Customer $customer, GdprRequest $request): void
    {
        DB::transaction(function () use ($customer, $request) {
            try {
                $request->addLog('Starting customer data deletion', ['customer_id' => $customer->id]);

                // Delete customer-related data
                $this->deleteCustomerRelatedData($customer, $request);

                // Delete the customer
                $customer->delete();
                $request->addLog('Customer deleted', ['customer_id' => $customer->id]);

                $request->markAsCompleted();
            } catch (\Exception $e) {
                $request->addLog('Error during customer deletion', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                $request->update(['status' => GdprRequest::STATUS_FAILED]);
                throw $e;
            }
        });
    }

    protected function deleteUserRelatedData(User $user, GdprRequest $request): void
    {
        // Delete cookie consents
        $consentsCount = \App\Models\CookieConsent::where('user_id', $user->id)->delete();
        $request->addLog('Cookie consents deleted', ['count' => $consentsCount]);

        // Delete consent tracking
        $trackingCount = \App\Models\ConsentTracking::where('user_id', $user->id)->delete();
        $request->addLog('Consent tracking deleted', ['count' => $trackingCount]);

        // Delete GDPR requests (except current one)
        $requestsCount = \App\Models\GdprRequest::where('user_id', $user->id)
            ->where('id', '!=', $request->id)
            ->delete();
        $request->addLog('Previous GDPR requests deleted', ['count' => $requestsCount]);

        // Delete search analytics
        if (class_exists(\App\Models\SearchAnalytic::class)) {
            $searchCount = \App\Models\SearchAnalytic::where('user_id', $user->id)->delete();
            $request->addLog('Search analytics deleted', ['count' => $searchCount]);
        }

        // Delete reviews
        if (class_exists(\App\Models\Review::class)) {
            $reviewsCount = \App\Models\Review::where('user_id', $user->id)->delete();
            $request->addLog('Reviews deleted', ['count' => $reviewsCount]);
        }

        // Delete product views
        if (class_exists(\App\Models\ProductView::class)) {
            $viewsCount = \App\Models\ProductView::where('user_id', $user->id)->delete();
            $request->addLog('Product views deleted', ['count' => $viewsCount]);
        }

        // Delete recommendation clicks
        if (class_exists(\App\Models\RecommendationClick::class)) {
            $clicksCount = \App\Models\RecommendationClick::where('user_id', $user->id)->delete();
            $request->addLog('Recommendation clicks deleted', ['count' => $clicksCount]);
        }

        // Delete sessions
        DB::table('sessions')->where('user_id', $user->id)->delete();
        $request->addLog('Sessions deleted');

        // Delete password reset tokens
        DB::table('password_reset_tokens')->where('email', $user->email)->delete();
        $request->addLog('Password reset tokens deleted');
    }

    protected function deleteCustomerRelatedData(Customer $customer, GdprRequest $request): void
    {
        // Delete addresses
        $addressesCount = Address::where('customer_id', $customer->id)->delete();
        $request->addLog('Addresses deleted', ['count' => $addressesCount]);

        // Delete cookie consents
        $consentsCount = \App\Models\CookieConsent::where('customer_id', $customer->id)->delete();
        $request->addLog('Cookie consents deleted', ['count' => $consentsCount]);

        // Delete consent tracking
        $trackingCount = \App\Models\ConsentTracking::where('customer_id', $customer->id)->delete();
        $request->addLog('Consent tracking deleted', ['count' => $trackingCount]);

        // Delete GDPR requests (except current one)
        $requestsCount = \App\Models\GdprRequest::where('customer_id', $customer->id)
            ->where('id', '!=', $request->id)
            ->delete();
        $request->addLog('Previous GDPR requests deleted', ['count' => $requestsCount]);

        // Note: Orders are typically not deleted for legal/compliance reasons
        // They are anonymized instead (see GdprDataAnonymizationService)
        $request->addLog('Orders preserved for legal compliance (should be anonymized separately)');
    }

    /**
     * Check if user can be deleted (no pending orders, etc.)
     */
    public function canDeleteUser(User $user): array
    {
        $customer = $user->customers()->first();
        
        if (!$customer) {
            return ['can_delete' => true, 'reasons' => []];
        }

        $reasons = [];

        // Check for pending orders
        $pendingOrders = Order::where('customer_id', $customer->id)
            ->whereIn('status', ['pending', 'processing', 'shipped'])
            ->count();

        if ($pendingOrders > 0) {
            $reasons[] = "User has {$pendingOrders} pending/active orders that must be completed or cancelled first.";
        }

        return [
            'can_delete' => empty($reasons),
            'reasons' => $reasons,
        ];
    }
}

