<?php

namespace App\Lunar\ActivityLog;

use Illuminate\Support\Collection;
use Spatie\Activitylog\Models\Activity;

/**
 * Helper class for working with Lunar Activity Log.
 * 
 * Provides convenience methods for querying and working with activity logs.
 * Lunar uses Spatie's laravel-activitylog package for activity logging.
 * See: https://docs.lunarphp.com/1.x/reference/activity-log
 */
class ActivityLogHelper
{
    /**
     * Get activity logs for a model.
     * 
     * @param \Illuminate\Database\Eloquent\Model $model
     * @param int|null $limit Maximum number of logs to retrieve
     * @return Collection<Activity>
     */
    public static function getForModel($model, ?int $limit = null): Collection
    {
        $query = Activity::forSubject($model)->latest();
        
        if ($limit) {
            $query->limit($limit);
        }
        
        return $query->get();
    }

    /**
     * Get activity logs for a model by event type.
     * 
     * @param \Illuminate\Database\Eloquent\Model $model
     * @param string $event Event type (e.g., 'created', 'updated', 'deleted')
     * @param int|null $limit Maximum number of logs to retrieve
     * @return Collection<Activity>
     */
    public static function getForModelByEvent($model, string $event, ?int $limit = null): Collection
    {
        $query = Activity::forSubject($model)
            ->where('event', $event)
            ->latest();
        
        if ($limit) {
            $query->limit($limit);
        }
        
        return $query->get();
    }

    /**
     * Get activity logs for a causer (user who performed the action).
     * 
     * @param \Illuminate\Database\Eloquent\Model $causer User model
     * @param int|null $limit Maximum number of logs to retrieve
     * @return Collection<Activity>
     */
    public static function getForCauser($causer, ?int $limit = null): Collection
    {
        $query = Activity::causedBy($causer)->latest();
        
        if ($limit) {
            $query->limit($limit);
        }
        
        return $query->get();
    }

    /**
     * Get activity logs by log name.
     * 
     * @param string $logName Log name (e.g., 'default', 'product', 'order')
     * @param int|null $limit Maximum number of logs to retrieve
     * @return Collection<Activity>
     */
    public static function getByLogName(string $logName, ?int $limit = null): Collection
    {
        $query = Activity::inLog($logName)->latest();
        
        if ($limit) {
            $query->limit($limit);
        }
        
        return $query->get();
    }

    /**
     * Get recent activity logs.
     * 
     * @param int $limit Number of recent logs to retrieve
     * @return Collection<Activity>
     */
    public static function getRecent(int $limit = 50): Collection
    {
        return Activity::latest()->limit($limit)->get();
    }

    /**
     * Check if a model has activity logs.
     * 
     * @param \Illuminate\Database\Eloquent\Model $model
     * @return bool
     */
    public static function hasActivity($model): bool
    {
        return Activity::forSubject($model)->exists();
    }

    /**
     * Get the latest activity log for a model.
     * 
     * @param \Illuminate\Database\Eloquent\Model $model
     * @return Activity|null
     */
    public static function getLatestForModel($model): ?Activity
    {
        return Activity::forSubject($model)->latest()->first();
    }

    /**
     * Get activity logs for a specific property change.
     * 
     * @param \Illuminate\Database\Eloquent\Model $model
     * @param string $property Property name
     * @return Collection<Activity>
     */
    public static function getForProperty($model, string $property): Collection
    {
        return Activity::forSubject($model)
            ->whereJsonContains('properties->attributes->' . $property)
            ->orWhereJsonContains('properties->old->' . $property)
            ->latest()
            ->get();
    }
}


