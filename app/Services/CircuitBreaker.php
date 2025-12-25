<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Circuit Breaker Pattern Implementation
 * 
 * Protects against cascading failures from external services.
 * Used for:
 * - External API calls
 * - Database operations under load
 * - Third-party integrations
 */
class CircuitBreaker
{
    protected string $name;
    protected int $failureThreshold;
    protected int $timeout;
    protected int $halfOpenTimeout;

    public function __construct(
        string $name,
        int $failureThreshold = 5,
        int $timeout = 60,
        int $halfOpenTimeout = 30
    ) {
        $this->name = $name;
        $this->failureThreshold = $failureThreshold;
        $this->timeout = $timeout;
        $this->halfOpenTimeout = $halfOpenTimeout;
    }

    /**
     * Execute operation with circuit breaker protection.
     */
    public function call(callable $operation, callable $fallback = null): mixed
    {
        $state = $this->getState();

        if ($state === 'open') {
            // Circuit is open - use fallback or throw exception
            if ($fallback) {
                Log::warning("CircuitBreaker: Circuit open, using fallback", ['name' => $this->name]);
                return $fallback();
            }

            throw new \RuntimeException("Circuit breaker is open for {$this->name}");
        }

        try {
            $result = $operation();
            
            // Success - reset failure count
            if ($state === 'half-open') {
                $this->setState('closed');
                $this->resetFailureCount();
                Log::info("CircuitBreaker: Circuit closed after success", ['name' => $this->name]);
            } else {
                $this->resetFailureCount();
            }

            return $result;
        } catch (\Exception $e) {
            $this->recordFailure();
            
            // Check if we should open the circuit
            if ($this->getFailureCount() >= $this->failureThreshold) {
                $this->setState('open');
                Log::error("CircuitBreaker: Circuit opened", [
                    'name' => $this->name,
                    'failures' => $this->getFailureCount(),
                ]);
            }

            // Use fallback if available
            if ($fallback) {
                return $fallback();
            }

            throw $e;
        }
    }

    /**
     * Get current circuit state.
     */
    protected function getState(): string
    {
        $state = Cache::get("circuit:{$this->name}:state", 'closed');
        
        // Check if we should transition from open to half-open
        if ($state === 'open') {
            $openedAt = Cache::get("circuit:{$this->name}:opened_at");
            if ($openedAt && now()->diffInSeconds($openedAt) >= $this->timeout) {
                $this->setState('half-open');
                return 'half-open';
            }
        }

        return $state;
    }

    /**
     * Set circuit state.
     */
    protected function setState(string $state): void
    {
        Cache::put("circuit:{$this->name}:state", $state, now()->addHours(24));
        
        if ($state === 'open') {
            Cache::put("circuit:{$this->name}:opened_at", now(), now()->addHours(24));
        }
    }

    /**
     * Record a failure.
     */
    protected function recordFailure(): void
    {
        $key = "circuit:{$this->name}:failures";
        $count = Cache::increment($key);
        
        if ($count === 1) {
            Cache::put($key, 1, now()->addMinutes(5));
        }
    }

    /**
     * Get failure count.
     */
    protected function getFailureCount(): int
    {
        return Cache::get("circuit:{$this->name}:failures", 0);
    }

    /**
     * Reset failure count.
     */
    protected function resetFailureCount(): void
    {
        Cache::forget("circuit:{$this->name}:failures");
    }

    /**
     * Get circuit breaker status.
     */
    public function getStatus(): array
    {
        return [
            'name' => $this->name,
            'state' => $this->getState(),
            'failures' => $this->getFailureCount(),
            'threshold' => $this->failureThreshold,
        ];
    }
}

