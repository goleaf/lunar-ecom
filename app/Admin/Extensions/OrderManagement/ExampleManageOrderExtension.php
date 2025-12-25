<?php

namespace App\Admin\Extensions\OrderManagement;

use Filament\Infolists\Components\Component;
use Filament\Infolists\Components\Entry;
use Filament\Infolists\Components\Group;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Lunar\Admin\Support\Extending\ViewPageExtension;

/**
 * Example extension for Manage Order page in Lunar admin panel.
 * 
 * This is a scaffolding example. For production use, refer to:
 * https://docs.lunarphp.com/1.x/admin/extending/order-management
 * 
 * Order management extensions allow you to customize the order view screen,
 * including infolists, aside sections, order summary, timeline, totals, shipping,
 * and transactions.
 */
class ExampleManageOrderExtension extends ViewPageExtension
{
    /**
     * Extend the main infolist schema.
     */
    public function extendInfolistSchema(): array
    {
        return [
            // Add custom infolist entries here
            // TextEntry::make('custom_field')
            //     ->label('Custom Field'),
        ];
    }

    /**
     * Extend the aside infolist schema.
     */
    public function extendInfolistAsideSchema(): array
    {
        return [
            // Add custom aside entries here
        ];
    }

    /**
     * Extend the customer entry.
     * 
     * Uncomment and implement when needed:
     * return TextEntry::make('customer.name');
     */
    // public function extendCustomerEntry(): Component
    // {
    //     // Customize customer entry display
    // }

    /**
     * Extend the tags section.
     * 
     * Uncomment and implement when needed.
     */
    // public function extendTagsSection(): Component
    // {
    //     // Customize tags section display
    // }

    /**
     * Extend the additional info section.
     * 
     * Uncomment and implement when needed.
     */
    // public function extendAdditionalInfoSection(): Component
    // {
    //     // Customize additional info section
    // }

    /**
     * Extend the shipping address infolist.
     * 
     * Uncomment and implement when needed.
     */
    // public function extendShippingAddressInfolist(): Component
    // {
    //     // Customize shipping address display
    // }

    /**
     * Extend the billing address infolist.
     * 
     * Uncomment and implement when needed.
     */
    // public function extendBillingAddressInfolist(): Component
    // {
    //     // Customize billing address display
    // }

    /**
     * Extend the address edit schema.
     */
    public function extendAddressEditSchema(): array
    {
        return [
            // Add custom address edit fields here
        ];
    }

    /**
     * Extend the order summary infolist.
     * 
     * Uncomment and implement when needed.
     */
    // public function extendOrderSummaryInfolist(): Section
    // {
    //     // Customize order summary section
    // }

    /**
     * Extend the order summary schema.
     */
    public function extendOrderSummarySchema(): array
    {
        return [
            // Add custom order summary entries here
        ];
    }

    /**
     * Extend the order summary new customer entry.
     * 
     * Uncomment and implement when needed.
     */
    // public function extendOrderSummaryNewCustomerEntry(): Entry
    // {
    //     // Customize new customer entry in order summary
    // }

    /**
     * Extend the order summary status entry.
     * 
     * Uncomment and implement when needed.
     */
    // public function extendOrderSummaryStatusEntry(): Entry
    // {
    //     // Customize status entry in order summary
    // }

    /**
     * Extend the order summary reference entry.
     * 
     * Uncomment and implement when needed.
     */
    // public function extendOrderSummaryReferenceEntry(): Entry
    // {
    //     // Customize reference entry in order summary
    // }

    /**
     * Extend the order summary customer reference entry.
     * 
     * Uncomment and implement when needed.
     */
    // public function extendOrderSummaryCustomerReferenceEntry(): Entry
    // {
    //     // Customize customer reference entry in order summary
    // }

    /**
     * Extend the order summary channel entry.
     * 
     * Uncomment and implement when needed.
     */
    // public function extendOrderSummaryChannelEntry(): Entry
    // {
    //     // Customize channel entry in order summary
    // }

    /**
     * Extend the order summary created at entry.
     * 
     * Uncomment and implement when needed.
     */
    // public function extendOrderSummaryCreatedAtEntry(): Entry
    // {
    //     // Customize created at entry in order summary
    // }

    /**
     * Extend the order summary placed at entry.
     * 
     * Uncomment and implement when needed.
     */
    // public function extendOrderSummaryPlacedAtEntry(): Entry
    // {
    //     // Customize placed at entry in order summary
    // }

    /**
     * Extend the timeline infolist.
     * 
     * Uncomment and implement when needed.
     */
    // public function extendTimelineInfolist(): Component
    // {
    //     // Customize timeline display
    // }

    /**
     * Extend the order totals aside schema.
     */
    public function extendOrderTotalsAsideSchema(): array
    {
        return [
            // Add custom totals aside entries here
        ];
    }

    /**
     * Extend the delivery instructions entry.
     * 
     * Uncomment and implement when needed.
     */
    // public function extendDeliveryInstructionsEntry(): TextEntry
    // {
    //     // Customize delivery instructions entry
    // }

    /**
     * Extend the order notes entry.
     * 
     * Uncomment and implement when needed.
     */
    // public function extendOrderNotesEntry(): TextEntry
    // {
    //     // Customize order notes entry
    // }

    /**
     * Extend the order totals infolist.
     * 
     * Uncomment and implement when needed.
     */
    // public function extendOrderTotalsInfolist(): Section
    // {
    //     // Customize order totals section
    // }

    /**
     * Extend the order totals schema.
     */
    public function extendOrderTotalsSchema(): array
    {
        return [
            // Add custom totals entries here
        ];
    }

    /**
     * Extend the subtotal entry.
     * 
     * Uncomment and implement when needed.
     */
    // public function extendSubTotalEntry(): TextEntry
    // {
    //     // Customize subtotal entry
    // }

    /**
     * Extend the discount total entry.
     * 
     * Uncomment and implement when needed.
     */
    // public function extendDiscountTotalEntry(): TextEntry
    // {
    //     // Customize discount total entry
    // }

    /**
     * Extend the shipping breakdown group.
     * 
     * Uncomment and implement when needed.
     */
    // public function extendShippingBreakdownGroup(): Group
    // {
    //     // Customize shipping breakdown group
    // }

    /**
     * Extend the tax breakdown group.
     * 
     * Uncomment and implement when needed.
     */
    // public function extendTaxBreakdownGroup(): Group
    // {
    //     // Customize tax breakdown group
    // }

    /**
     * Extend the total entry.
     * 
     * Uncomment and implement when needed.
     */
    // public function extendTotalEntry(): TextEntry
    // {
    //     // Customize total entry
    // }

    /**
     * Extend the paid entry.
     * 
     * Uncomment and implement when needed.
     */
    // public function extendPaidEntry(): TextEntry
    // {
    //     // Customize paid entry
    // }

    /**
     * Extend the refund entry.
     * 
     * Uncomment and implement when needed.
     */
    // public function extendRefundEntry(): TextEntry
    // {
    //     // Customize refund entry
    // }

    /**
     * Extend the shipping infolist.
     * 
     * Uncomment and implement when needed.
     */
    // public function extendShippingInfolist(): Section
    // {
    //     // Customize shipping section
    // }

    /**
     * Extend the transactions infolist.
     * 
     * Uncomment and implement when needed.
     */
    // public function extendTransactionsInfolist(): Component
    // {
    //     // Customize transactions display
    // }

    /**
     * Extend the transactions repeatable entry.
     * 
     * Uncomment and implement when needed.
     */
    // public function extendTransactionsRepeatableEntry(): RepeatableEntry
    // {
    //     // Customize transactions repeatable entry
    // }
}

