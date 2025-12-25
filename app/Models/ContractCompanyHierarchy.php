<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Lunar\Models\Customer;

/**
 * Contract Company Hierarchy Model
 * 
 * Manages company hierarchies for B2B contracts.
 * Allows parent companies to have subsidiaries, divisions, branches, etc.
 */
class ContractCompanyHierarchy extends Model
{
    use HasFactory;

    protected $table = 'lunar_contract_company_hierarchies';

    protected $fillable = [
        'parent_customer_id',
        'child_customer_id',
        'relationship_type',
        'inherit_contracts',
        'meta',
    ];

    protected $casts = [
        'inherit_contracts' => 'boolean',
        'meta' => 'array',
    ];

    // Relationship type constants
    const RELATIONSHIP_SUBSIDIARY = 'subsidiary';
    const RELATIONSHIP_DIVISION = 'division';
    const RELATIONSHIP_BRANCH = 'branch';

    /**
     * Get the parent customer.
     */
    public function parentCustomer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'parent_customer_id');
    }

    /**
     * Get the child customer.
     */
    public function childCustomer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'child_customer_id');
    }

    /**
     * Get all contracts that should be inherited by child.
     */
    public function getInheritedContracts(): \Illuminate\Support\Collection
    {
        if (!$this->inherit_contracts) {
            return collect();
        }

        return \App\Models\B2BContract::forCustomer($this->parent_customer_id)
            ->active()
            ->get();
    }
}

