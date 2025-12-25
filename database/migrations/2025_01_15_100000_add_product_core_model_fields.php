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
        Schema::table($this->prefix.'products', function (Blueprint $table) {
            // Visibility (public, private, scheduled)
            $table->enum('visibility', ['public', 'private', 'scheduled'])
                ->default('public')
                ->after('status')
                ->index();

            // Descriptions
            $table->text('short_description')->nullable()->after('visibility');
            $table->longText('full_description')->nullable()->after('short_description');
            $table->longText('technical_description')->nullable()->after('full_description');

            // SEO Meta fields
            $table->string('meta_title')->nullable()->after('technical_description');
            $table->text('meta_description')->nullable()->after('meta_title');
            $table->text('meta_keywords')->nullable()->after('meta_description');

            // Publishing timestamps
            $table->timestamp('published_at')->nullable()->after('meta_keywords')->index();
            $table->timestamp('scheduled_publish_at')->nullable()->after('published_at')->index();
            $table->timestamp('scheduled_unpublish_at')->nullable()->after('scheduled_publish_at')->index();

            // Product locking (prevent edits while live orders exist)
            $table->boolean('is_locked')->default(false)->after('scheduled_unpublish_at')->index();
            $table->foreignId('locked_by')->nullable()->after('is_locked')
                ->constrained('users')->nullOnDelete();
            $table->timestamp('locked_at')->nullable()->after('locked_by');
            $table->text('lock_reason')->nullable()->after('locked_at');

            // Version tracking
            $table->unsignedInteger('version')->default(1)->after('lock_reason');
            $table->foreignId('parent_version_id')->nullable()->after('version')
                ->nullable();

            // Indexes for common queries
            $table->index(['status', 'visibility', 'published_at']);
            $table->index(['is_locked', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table($this->prefix.'products', function (Blueprint $table) {
            $table->dropForeign(['locked_by']);
            $table->dropForeign(['parent_version_id']);
            
            $table->dropIndex(['status', 'visibility', 'published_at']);
            $table->dropIndex(['is_locked', 'status']);
            
            $table->dropColumn([
                'visibility',
                'short_description',
                'full_description',
                'technical_description',
                'meta_title',
                'meta_description',
                'meta_keywords',
                'published_at',
                'scheduled_publish_at',
                'scheduled_unpublish_at',
                'is_locked',
                'locked_by',
                'locked_at',
                'lock_reason',
                'version',
                'parent_version_id',
            ]);
        });
    }
};

