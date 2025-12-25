<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

/**
 * Referral Code Generator Service
 * 
 * Generates unique, short, case-insensitive codes with safe alphabet
 * (no 0/O, 1/I to avoid confusion).
 */
class ReferralCodeGeneratorService
{
    /**
     * Safe alphabet: A-Z excluding 0, O, 1, I
     * This prevents confusion between similar characters
     */
    protected const SAFE_ALPHABET = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

    /**
     * Default code length
     */
    protected const DEFAULT_LENGTH = 8;

    /**
     * Minimum code length
     */
    protected const MIN_LENGTH = 6;

    /**
     * Maximum code length
     */
    protected const MAX_LENGTH = 10;

    /**
     * Generate a unique referral code.
     * 
     * @param int $length Code length (6-10 characters)
     * @param string|null $prefix Optional prefix
     * @return string
     */
    public function generate(int $length = self::DEFAULT_LENGTH, ?string $prefix = null): string
    {
        // Validate length
        $length = max(self::MIN_LENGTH, min(self::MAX_LENGTH, $length));

        // Generate code
        $code = $this->generateCode($length);

        // Add prefix if provided
        if ($prefix) {
            $code = strtoupper($prefix) . $code;
        }

        // Ensure uniqueness (check both users and reserved codes)
        while ($this->codeExists($code) || $this->isReserved($code)) {
            $code = $this->generateCode($length);
            if ($prefix) {
                $code = strtoupper($prefix) . $code;
            }
        }

        return $code;
    }

    /**
     * Check if code is reserved.
     */
    protected function isReserved(string $code): bool
    {
        $code = strtoupper($code);

        return DB::table('reserved_referral_codes')
            ->whereRaw('UPPER(code) = ?', [$code])
            ->where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->exists();
    }

    /**
     * Generate a code from safe alphabet.
     */
    protected function generateCode(int $length): string
    {
        $code = '';
        $alphabetLength = strlen(self::SAFE_ALPHABET);

        for ($i = 0; $i < $length; $i++) {
            $code .= self::SAFE_ALPHABET[random_int(0, $alphabetLength - 1)];
        }

        return $code;
    }

    /**
     * Check if a code already exists (case-insensitive).
     */
    public function codeExists(string $code): bool
    {
        $code = strtoupper($code);

        return DB::table('users')
            ->whereRaw('UPPER(referral_code) = ?', [$code])
            ->exists();
    }

    /**
     * Validate a code format.
     */
    public function isValidFormat(string $code): bool
    {
        $code = strtoupper($code);
        $length = strlen($code);

        if ($length < self::MIN_LENGTH || $length > self::MAX_LENGTH) {
            return false;
        }

        // Check all characters are in safe alphabet
        for ($i = 0; $i < $length; $i++) {
            if (strpos(self::SAFE_ALPHABET, $code[$i]) === false) {
                return false;
            }
        }

        return true;
    }

    /**
     * Generate a vanity code (reserved for VIP users).
     * 
     * @param string $vanity The desired vanity string
     * @param int $suffixLength Length of random suffix
     * @return string|null Returns null if vanity is already taken
     */
    public function generateVanityCode(string $vanity, int $suffixLength = 3): ?string
    {
        $vanity = strtoupper($vanity);
        
        // Validate vanity doesn't contain unsafe characters
        if (!$this->isValidFormat($vanity)) {
            return null;
        }

        // Generate with suffix
        $suffix = $this->generateCode($suffixLength);
        $code = $vanity . $suffix;

        // Check if already exists
        if ($this->codeExists($code)) {
            return null;
        }

        return $code;
    }

    /**
     * Reserve a vanity code (admin function).
     * 
     * @param string $code The exact code to reserve
     * @return bool True if reserved successfully
     */
    public function reserveVanityCode(string $code): bool
    {
        $code = strtoupper($code);

        if (!$this->isValidFormat($code)) {
            return false;
        }

        if ($this->codeExists($code)) {
            return false;
        }

        // Check if already reserved
        if (DB::table('reserved_referral_codes')
            ->whereRaw('UPPER(code) = ?', [$code])
            ->where('is_active', true)
            ->exists()) {
            return false;
        }

        return true;
    }

    /**
     * Regenerate code for a user.
     */
    public function regenerateForUser(User $user, ?int $length = null): string
    {
        $length = $length ?? self::DEFAULT_LENGTH;
        $newCode = $this->generate($length);

        $user->update([
            'referral_code' => $newCode,
            'referral_link_slug' => Str::slug($newCode),
        ]);

        return $newCode;
    }

    /**
     * Generate slug from code.
     */
    public function generateSlug(string $code): string
    {
        return Str::slug(strtoupper($code));
    }
}

