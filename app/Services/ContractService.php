<?php

namespace App\Services;

use App\Models\B2BContract;
use App\Models\PriceList;
use App\Models\ContractPrice;
use App\Models\ContractRule;
use App\Models\ContractCreditLimit;
use App\Models\ContractAudit;
use App\Models\ContractCompanyHierarchy;
use Lunar\Models\Customer;
use Lunar\Models\ProductVariant;
use Illuminate\Support\Collection;

/**
 * Contract Service
 * 
 * Handles B2B contract management, validation, and business logic.
 */
class ContractService
{
    /**
     * Get active contracts for a customer.
     * 
     * @param Customer $customer
     * @param bool $includeInherited Whether to include contracts from parent companies
     * @return Collection
     */
    public function getActiveContractsForCustomer(Customer $customer, bool $includeInherited = true): Collection
    {
        $contracts = B2BContract::forCustomer($customer->id)
            ->active()
            ->byPriority()
            ->get();

        // Include inherited contracts from parent companies
        if ($includeInherited) {
            $parentContracts = $this->getInheritedContracts($customer);
            $contracts = $contracts->merge($parentContracts)->unique('id');
        }

        return $contracts;
    }

    /**
     * Get contracts inherited from parent companies.
     * 
     * @param Customer $customer
     * @return Collection
     */
    public function getInheritedContracts(Customer $customer): Collection
    {
        $hierarchies = ContractCompanyHierarchy::where('child_customer_id', $customer->id)
            ->where('inherit_contracts', true)
            ->get();

        $contracts = collect();

        foreach ($hierarchies as $hierarchy) {
            $parentContracts = B2BContract::forCustomer($hierarchy->parent_customer_id)
                ->active()
                ->get();
            $contracts = $contracts->merge($parentContracts);
        }

        return $contracts->unique('id');
    }

    /**
     * Get the best contract for a customer (highest priority active contract).
     * 
     * @param Customer $customer
     * @return B2BContract|null
     */
    public function getBestContractForCustomer(Customer $customer): ?B2BContract
    {
        return $this->getActiveContractsForCustomer($customer)->first();
    }

    /**
     * Check if customer has sufficient credit for an order.
     * 
     * @param Customer $customer
     * @param int $orderAmount Order amount in minor currency units
     * @return bool
     */
    public function hasSufficientCredit(Customer $customer, int $orderAmount): bool
    {
        $contract = $this->getBestContractForCustomer($customer);
        
        if (!$contract) {
            return false; // No contract = no credit
        }

        $creditLimit = $contract->creditLimit()->first();
        
        if (!$creditLimit) {
            return false; // No credit limit set
        }

        return $creditLimit->hasSufficientCredit($orderAmount);
    }

    /**
     * Get contract rules that apply to a context.
     * 
     * @param B2BContract $contract
     * @param array $context Context data (cart, order, etc.)
     * @return Collection
     */
    public function getApplicableRules(B2BContract $contract, array $context): Collection
    {
        return $contract->activeRules()
            ->byPriority()
            ->get()
            ->filter(function ($rule) use ($context) {
                return $rule->matches($context);
            });
    }

    /**
     * Check if contract price should override promotions.
     * 
     * @param B2BContract $contract
     * @return bool
     */
    public function shouldOverridePromotions(B2BContract $contract): bool
    {
        return $contract->activeRules()
            ->ofType(ContractRule::TYPE_PROMOTION_OVERRIDE)
            ->exists();
    }

    /**
     * Get allowed payment methods for a contract.
     * 
     * @param B2BContract $contract
     * @return array
     */
    public function getAllowedPaymentMethods(B2BContract $contract): array
    {
        $rule = $contract->activeRules()
            ->ofType(ContractRule::TYPE_PAYMENT_METHOD)
            ->byPriority()
            ->first();

        if (!$rule) {
            return []; // No rule = no restrictions (use default)
        }

        return $rule->getActions()['allowed_methods'] ?? [];
    }

    /**
     * Get shipping rules for a contract.
     * 
     * @param B2BContract $contract
     * @return array
     */
    public function getShippingRules(B2BContract $contract): array
    {
        $rule = $contract->activeRules()
            ->ofType(ContractRule::TYPE_SHIPPING)
            ->byPriority()
            ->first();

        if (!$rule) {
            return [];
        }

        return $rule->getActions();
    }

    /**
     * Create a new contract.
     * 
     * @param array $data
     * @return B2BContract
     */
    public function createContract(array $data): B2BContract
    {
        // Generate contract ID if not provided
        if (empty($data['contract_id'])) {
            $data['contract_id'] = $this->generateContractId();
        }

        $contract = B2BContract::create($data);

        // Create default credit limit if not provided
        if (!isset($data['credit_limit'])) {
            ContractCreditLimit::create([
                'contract_id' => $contract->id,
                'credit_limit' => 0,
                'payment_terms' => ContractCreditLimit::TERMS_NET_30,
                'payment_terms_days' => 30,
            ]);
        }

        // Log audit
        ContractAudit::create([
            'contract_id' => $contract->id,
            'audit_type' => ContractAudit::TYPE_CONTRACT_CHANGE,
            'action' => ContractAudit::ACTION_CREATED,
            'description' => "Contract created: {$contract->name}",
        ]);

        return $contract;
    }

    /**
     * Generate a unique contract ID.
     * 
     * @return string
     */
    protected function generateContractId(): string
    {
        do {
            $contractId = 'CTR-' . strtoupper(substr(md5(uniqid()), 0, 8));
        } while (B2BContract::where('contract_id', $contractId)->exists());

        return $contractId;
    }

    /**
     * Check for contracts expiring soon and log alerts.
     * 
     * @param int $daysAhead Number of days ahead to check
     * @return int Number of alerts created
     */
    public function checkExpiringContracts(int $daysAhead = 30): int
    {
        $expiryDate = now()->addDays($daysAhead);

        $expiringContracts = B2BContract::where('status', B2BContract::STATUS_ACTIVE)
            ->where('approval_state', B2BContract::APPROVAL_APPROVED)
            ->whereNotNull('valid_to')
            ->whereBetween('valid_to', [now(), $expiryDate])
            ->get();

        $alertCount = 0;

        foreach ($expiringContracts as $contract) {
            // Check if alert already exists
            $existingAlert = ContractAudit::where('contract_id', $contract->id)
                ->where('audit_type', ContractAudit::TYPE_EXPIRY_ALERT)
                ->whereDate('created_at', today())
                ->exists();

            if (!$existingAlert) {
                ContractAudit::logExpiryAlert($contract);
                $alertCount++;
            }
        }

        return $alertCount;
    }
}

