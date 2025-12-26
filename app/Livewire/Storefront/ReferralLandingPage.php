<?php

namespace App\Livewire\Storefront;

use App\Lunar\Languages\LanguageHelper;
use App\Models\ReferralProgram;
use App\Models\User;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Facades\URL;
use Livewire\Component;

class ReferralLandingPage extends Component
{
    public string $locale;
    public string $code;

    public ?User $referrer = null;
    public ?ReferralProgram $program = null;

    public array $languages = [];
    public string $canonical = '';
    public array $hreflangs = [];
    public string $shareUrl = '';

    public function mount(string $locale, string $code): void
    {
        $this->locale = strtolower($locale);
        $this->code = $code;

        $language = LanguageHelper::findByCode($this->locale);
        $defaultLanguage = LanguageHelper::getDefault();

        if (!$language && $defaultLanguage) {
            $this->redirectRoute('storefront.referrals.landing', [
                'locale' => $defaultLanguage->code,
                'code' => $this->code,
            ], navigate: true);
            return;
        }

        // Prefer middleware-provided values to avoid duplicate queries.
        $this->referrer = request()->attributes->get('referral_referrer');
        $this->program = request()->attributes->get('referral_program');

        if (!$this->referrer) {
            $this->referrer = User::query()
                ->whereRaw('UPPER(referral_code) = ?', [strtoupper($this->code)])
                ->orWhereRaw('UPPER(referral_link_slug) = ?', [strtoupper($this->code)])
                ->first();
        }

        if (!$this->referrer || $this->referrer->status !== 'active' || $this->referrer->referral_blocked) {
            abort(404);
        }

        if (!$this->program) {
            $this->program = ReferralProgram::active()
                ->get()
                ->first(fn (ReferralProgram $p) => $p->isEligibleForUser(null));
        }

        $this->languages = LanguageHelper::getAll()
            ->map(fn ($lang) => ['code' => $lang->code, 'name' => $lang->name, 'is_default' => (bool) $lang->default])
            ->values()
            ->all();

        $this->canonical = request()->url();
        $this->shareUrl = URL::current();

        $this->hreflangs = array_map(function ($lang) {
            return [
                'hreflang' => $lang['code'],
                'href' => route('storefront.referrals.landing', ['locale' => $lang['code'], 'code' => $this->referrer?->referral_code ?: $this->code]),
            ];
        }, $this->languages);

        if ($defaultLanguage) {
            $this->hreflangs[] = [
                'hreflang' => 'x-default',
                'href' => route('storefront.referrals.landing', ['locale' => $defaultLanguage->code, 'code' => $this->referrer?->referral_code ?: $this->code]),
            ];
        }
    }

    public function render()
    {
        $pageMeta = new HtmlString(view('storefront.referrals._meta', [
            'canonical' => $this->canonical ?: request()->url(),
            'hreflangs' => $this->hreflangs,
            'noindex' => true,
        ])->render());

        return view('livewire.storefront.referral-landing-page')
            ->layout('storefront.layout', [
                'pageTitle' => 'Referral',
                'pageMeta' => $pageMeta,
            ]);
    }
}


