<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Lunar\Base\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table($this->prefix.'product_schedules', function (Blueprint $table) {
            // Advanced scheduling fields
            $table->enum('schedule_type', ['one_time', 'recurring'])->default('one_time')->after('type')->index();
            $table->date('start_date')->nullable()->after('scheduled_at');
            $table->date('end_date')->nullable()->after('start_date');
            $table->json('days_of_week')->nullable()->comment('Array of day numbers (0=Sunday, 6=Saturday)');
            $table->time('time_start')->nullable()->after('days_of_week');
            $table->time('time_end')->nullable()->after('time_start');
            $table->string('timezone')->nullable()->default('UTC')->after('time_end');
            $table->string('season_tag')->nullable()->comment('For seasonal products (e.g., "christmas", "summer")')->index();
            $table->boolean('auto_unpublish_after_season')->default(false);
            
            // Coming Soon support
            $table->boolean('is_coming_soon')->default(false)->index();
            $table->text('coming_soon_message')->nullable();
            
            // Bulk scheduling
            $table->string('bulk_schedule_id')->nullable()->index()->comment('For grouping bulk schedules');
            $table->json('applied_to')->nullable()->comment('Product IDs, category IDs, or tag IDs for bulk operations');
            
            // Notification settings
            $table->integer('notification_hours_before')->nullable()->comment('Hours before schedule to send notification');
            $table->timestamp('notification_scheduled_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table($this->prefix.'product_schedules', function (Blueprint $table) {
            $table->dropColumn([
                'schedule_type',
                'start_date',
                'end_date',
                'days_of_week',
                'time_start',
                'time_end',
                'timezone',
                'season_tag',
                'auto_unpublish_after_season',
                'is_coming_soon',
                'coming_soon_message',
                'bulk_schedule_id',
                'applied_to',
                'notification_hours_before',
                'notification_scheduled_at',
            ]);
        });
    }
};

