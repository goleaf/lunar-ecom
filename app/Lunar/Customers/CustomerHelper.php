<?php

namespace App\Lunar\Customers;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Lunar\Models\Customer;
use Lunar\Models\CustomerGroup;

/**
 * Helper class for working with Lunar Customers.
 * 
 * Provides convenience methods for managing customers, customer groups, and their relationships.
 * See: https://docs.lunarphp.com/1.x/reference/customers
 */
class CustomerHelper
{
    /**
     * Create a new customer.
     * 
     * @param array $data Customer data (title, first_name, last_name, company_name, vat_no, meta, etc.)
     * @return Customer
     */
    public static function create(array $data): Customer
    {
        return Customer::create($data);
    }

    /**
     * Find a customer by ID.
     * 
     * @param int $id
     * @return Customer|null
     */
    public static function find(int $id): ?Customer
    {
        return Customer::find($id);
    }

    /**
     * Get all customers.
     * 
     * @return Collection
     */
    public static function all(): Collection
    {
        return Customer::all();
    }

    /**
     * Attach a user to a customer.
     * 
     * @param Customer $customer
     * @param \App\Models\User|int $user
     * @return void
     */
    public static function attachUser(Customer $customer, \App\Models\User|int $user): void
    {
        $userId = $user instanceof \App\Models\User ? $user->id : $user;
        $customer->users()->attach($userId);
    }

    /**
     * Sync users for a customer.
     * 
     * @param Customer $customer
     * @param array|Collection $userIds
     * @return void
     */
    public static function syncUsers(Customer $customer, array|Collection $userIds): void
    {
        if ($userIds instanceof Collection) {
            $userIds = $userIds->toArray();
        }
        $customer->users()->sync($userIds);
    }

    /**
     * Detach a user from a customer.
     * 
     * @param Customer $customer
     * @param \App\Models\User|int $user
     * @return void
     */
    public static function detachUser(Customer $customer, \App\Models\User|int $user): void
    {
        $userId = $user instanceof \App\Models\User ? $user->id : $user;
        $customer->users()->detach($userId);
    }

    /**
     * Get users for a customer.
     * 
     * @param Customer $customer
     * @return Collection
     */
    public static function getUsers(Customer $customer): Collection
    {
        return $customer->users;
    }

    /**
     * Attach a customer to a customer group.
     * 
     * @param Customer $customer
     * @param CustomerGroup|int $customerGroup
     * @return void
     */
    public static function attachCustomerGroup(Customer $customer, CustomerGroup|int $customerGroup): void
    {
        $groupId = $customerGroup instanceof CustomerGroup ? $customerGroup->id : $customerGroup;
        $customer->customerGroups()->attach($groupId);
    }

    /**
     * Sync customer groups for a customer.
     * 
     * @param Customer $customer
     * @param array|Collection $customerGroupIds
     * @return void
     */
    public static function syncCustomerGroups(Customer $customer, array|Collection $customerGroupIds): void
    {
        if ($customerGroupIds instanceof Collection) {
            $customerGroupIds = $customerGroupIds->toArray();
        }
        $customer->customerGroups()->sync($customerGroupIds);
    }

    /**
     * Detach a customer group from a customer.
     * 
     * @param Customer $customer
     * @param CustomerGroup|int $customerGroup
     * @return void
     */
    public static function detachCustomerGroup(Customer $customer, CustomerGroup|int $customerGroup): void
    {
        $groupId = $customerGroup instanceof CustomerGroup ? $customerGroup->id : $customerGroup;
        $customer->customerGroups()->detach($groupId);
    }

    /**
     * Get customer groups for a customer.
     * 
     * @param Customer $customer
     * @return Collection
     */
    public static function getCustomerGroups(Customer $customer): Collection
    {
        return $customer->customerGroups;
    }

    /**
     * Create a customer group.
     * 
     * @param string $name
     * @param string $handle Must be unique
     * @param bool $default Whether this should be the default group
     * @return CustomerGroup
     */
    public static function createCustomerGroup(string $name, string $handle, bool $default = false): CustomerGroup
    {
        return CustomerGroup::create([
            'name' => $name,
            'handle' => $handle,
            'default' => $default,
        ]);
    }

    /**
     * Get all customer groups.
     * 
     * @return Collection
     */
    public static function getAllCustomerGroups(): Collection
    {
        return CustomerGroup::all();
    }

    /**
     * Get the default customer group.
     * 
     * @return CustomerGroup|null
     */
    public static function getDefaultCustomerGroup(): ?CustomerGroup
    {
        return CustomerGroup::where('default', true)->first();
    }

    /**
     * Find a customer group by handle.
     * 
     * @param string $handle
     * @return CustomerGroup|null
     */
    public static function findCustomerGroupByHandle(string $handle): ?CustomerGroup
    {
        return CustomerGroup::where('handle', $handle)->first();
    }

    /**
     * Schedule a customer group for a model (using HasCustomerGroups trait).
     * 
     * @param \Illuminate\Database\Eloquent\Model $model
     * @param CustomerGroup|int|array|Collection $customerGroups
     * @param Carbon|null $startDate
     * @param Carbon|null $endDate
     * @param array $pivotData
     * @return void
     */
    public static function scheduleCustomerGroup(
        \Illuminate\Database\Eloquent\Model $model,
        CustomerGroup|int|array|Collection $customerGroups,
        ?Carbon $startDate = null,
        ?Carbon $endDate = null,
        array $pivotData = []
    ): void {
        $model->scheduleCustomerGroup($customerGroups, $startDate, $endDate, $pivotData);
    }

    /**
     * Unschedule a customer group for a model.
     * 
     * @param \Illuminate\Database\Eloquent\Model $model
     * @param CustomerGroup|int $customerGroup
     * @param array $pivotData
     * @return void
     */
    public static function unscheduleCustomerGroup(
        \Illuminate\Database\Eloquent\Model $model,
        CustomerGroup|int $customerGroup,
        array $pivotData = []
    ): void {
        $model->unscheduleCustomerGroup($customerGroup, $pivotData);
    }

    /**
     * Get customer for a user.
     * 
     * @param \App\Models\User $user
     * @return Customer|null
     */
    public static function getCustomerForUser(\App\Models\User $user): ?Customer
    {
        return $user->latestCustomer();
    }

    /**
     * Create or get customer for a user.
     * 
     * @param \App\Models\User $user
     * @param array $customerData Optional customer data
     * @return Customer
     */
    public static function getOrCreateCustomerForUser(\App\Models\User $user, array $customerData = []): Customer
    {
        $customer = $user->latestCustomer();

        if (!$customer) {
            $customer = Customer::create(array_merge([
                'first_name' => $user->name,
                'last_name' => '',
            ], $customerData));

            $customer->users()->attach($user->id);
        }

        return $customer;
    }
}

