<?php

namespace App\Http\Controllers\Gdpr;

use App\Http\Controllers\Controller;
use App\Models\PrivacyPolicy;
use Illuminate\Http\Request;

class PrivacyPolicyController extends Controller
{
    /**
     * Show current privacy policy
     */
    public function show()
    {
        $policy = PrivacyPolicy::current()->first();

        if (!$policy) {
            abort(404, 'Privacy policy not found');
        }

        return view('gdpr.privacy-policy', [
            'policy' => $policy,
        ]);
    }

    /**
     * Show privacy policy by version
     */
    public function version(string $version)
    {
        $policy = PrivacyPolicy::where('version', $version)->firstOrFail();

        return view('gdpr.privacy-policy', [
            'policy' => $policy,
        ]);
    }

    /**
     * List all privacy policy versions
     */
    public function index()
    {
        $policies = PrivacyPolicy::active()
            ->orderBy('effective_date', 'desc')
            ->get();

        return view('gdpr.privacy-policy-versions', [
            'policies' => $policies,
        ]);
    }
}
