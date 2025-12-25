<?php

namespace App\Services;

use App\Models\GdprRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Lunar\Models\Customer;
use Lunar\Models\Order;
use Lunar\Models\Address;

class GdprDataAnonymizationService
{
    /**
     * Anonymize user data
     */
    public function anonymizeUserData(User $user, GdprRequest $request): void
    {
        DB::transaction(function () use ($user, $request) {
            try {
                $request->addLog('Starting data anonymization process', ['user_id' => $user->id]);

                $customer = $user->customers()->first();

                // Anonymize user account
                $this->anonymizeUser($user, $request);

                // Anonymize customer data if exists
                if ($customer) {
                    $this->anonymizeCustomer($customer, $request);
                }

                $request->markAsCompleted();
            } catch (\Exception $e) {
                $request->addLog('Error during anonymization', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                $request->update(['status' => GdprRequest::STATUS_FAILED]);
                throw $e;
            }
        });
    }

    /**
     * Anonymize customer data
     */
    public function anonymizeCustomerData(Customer $customer, GdprRequest $request): void
    {
        DB::transaction(function () use ($customer, $request) {
            try {
                $request->addLog('Starting customer data anonymization', ['customer_id' => $customer->id]);

                $this->anonymizeCustomer($customer, $request);

                $request->markAsCompleted();
            } catch (\Exception $e) {
                $request->addLog('Error during customer anonymization', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                $request->update(['status' => GdprRequest::STATUS_FAILED]);
                throw $e;
            }
        });
    }

    protected function anonymizeUser(User $user, GdprRequest $request): void
    {
        $anonymizedEmail = 'deleted_' . $user->id . '_' . time() . '@deleted.local';
        $anonymizedName = 'Deleted User';

        $user->update([
            'name' => $anonymizedName,
            'email' => $anonymizedEmail,
            'email_verified_at' => null,
        ]);

        $request->addLog('User anonymized', [
            'user_id' => $user->id,
            'old_email' => '***', // Don't log actual email
        ]);
    }

    protected function anonymizeCustomer(Customer $customer, GdprRequest $request): void
    {
        // Anonymize customer record
        $customer->update([
            'title' => null,
            'first_name' => 'Deleted',
            'last_name' => 'Customer',
            'company_name' => null,
            'vat_no' => null,
            'meta' => null,
        ]);

        $request->addLog('Customer anonymized', ['customer_id' => $customer->id]);

        // Anonymize addresses
        $this->anonymizeAddresses($customer, $request);

        // Anonymize orders (preserve order data but remove personal info)
        $this->anonymizeOrders($customer, $request);
    }

    protected function anonymizeAddresses(Customer $customer, GdprRequest $request): void
    {
        $addresses = Address::where('customer_id', $customer->id)->get();

        foreach ($addresses as $address) {
            $address->update([
                'title' => null,
                'first_name' => 'Deleted',
                'last_name' => 'Customer',
                'company_name' => null,
                'line_one' => '[Anonymized]',
                'line_two' => null,
                'line_three' => null,
                'city' => '[Anonymized]',
                'state' => null,
                'postcode' => null,
                'contact_email' => null,
                'contact_phone' => null,
                'delivery_instructions' => null,
            ]);
        }

        $request->addLog('Addresses anonymized', ['count' => $addresses->count()]);
    }

    protected function anonymizeOrders(Customer $customer, GdprRequest $request): void
    {
        $orders = Order::where('customer_id', $customer->id)->get();

        foreach ($orders as $order) {
            // Anonymize order addresses
            foreach ($order->addresses as $address) {
                $address->update([
                    'title' => null,
                    'first_name' => 'Deleted',
                    'last_name' => 'Customer',
                    'company_name' => null,
                    'line_one' => '[Anonymized]',
                    'line_two' => null,
                    'line_three' => null,
                    'city' => '[Anonymized]',
                    'state' => null,
                    'postcode' => null,
                    'contact_email' => null,
                    'contact_phone' => null,
                    'delivery_instructions' => null,
                ]);
            }

            // Update order metadata to mark as anonymized
            $meta = $order->meta ?? [];
            $meta['anonymized_at'] = now()->toIso8601String();
            $meta['anonymized'] = true;
            $order->update(['meta' => $meta]);
        }

        $request->addLog('Orders anonymized', ['count' => $orders->count()]);
    }

    /**
     * Generate anonymized identifier
     */
    protected function generateAnonymizedId(int $originalId): string
    {
        return 'anon_' . hash('sha256', $originalId . config('app.key'));
    }
}

