<?php

namespace App\Http\Controllers\Gdpr;

use App\Http\Controllers\Controller;
use App\Models\GdprRequest;
use App\Services\GdprDataExportService;
use App\Services\GdprDataDeletionService;
use App\Services\GdprDataAnonymizationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Lunar\Models\Customer;

class GdprRequestController extends Controller
{
    protected GdprDataExportService $exportService;
    protected GdprDataDeletionService $deletionService;
    protected GdprDataAnonymizationService $anonymizationService;

    public function __construct(
        GdprDataExportService $exportService,
        GdprDataDeletionService $deletionService,
        GdprDataAnonymizationService $anonymizationService
    ) {
        $this->exportService = $exportService;
        $this->deletionService = $deletionService;
        $this->anonymizationService = $anonymizationService;
    }

    /**
     * Create a GDPR request
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|in:export,deletion,anonymization,rectification',
            'email' => 'required|email',
        ]);

        $user = Auth::user();
        $customer = $user ? $user->customers()->first() : null;

        // If user is authenticated, use their email
        $email = $user ? $user->email : $validated['email'];

        $gdprRequest = GdprRequest::create([
            'type' => $validated['type'],
            'status' => GdprRequest::STATUS_PENDING,
            'user_id' => $user?->id,
            'customer_id' => $customer?->id,
            'email' => $email,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'request_data' => $request->except(['email']),
        ]);

        // Send verification email
        $this->sendVerificationEmail($gdprRequest);

        return response()->json([
            'success' => true,
            'message' => 'GDPR request created. Please check your email to verify the request.',
            'request_id' => $gdprRequest->id,
        ]);
    }

    /**
     * Verify GDPR request
     */
    public function verify(Request $request, string $token)
    {
        $gdprRequest = GdprRequest::where('verification_token', $token)
            ->whereNull('verified_at')
            ->firstOrFail();

        $gdprRequest->markAsVerified();

        // Process the request based on type
        try {
            match ($gdprRequest->type) {
                GdprRequest::TYPE_EXPORT => $this->processExport($gdprRequest),
                GdprRequest::TYPE_DELETION => $this->processDeletion($gdprRequest),
                GdprRequest::TYPE_ANONYMIZATION => $this->processAnonymization($gdprRequest),
                GdprRequest::TYPE_RECTIFICATION => $this->processRectification($gdprRequest),
            };
        } catch (\Exception $e) {
            $gdprRequest->addLog('Processing error', ['error' => $e->getMessage()]);
            $gdprRequest->update(['status' => GdprRequest::STATUS_FAILED]);
        }

        return view('gdpr.verification-success', [
            'request' => $gdprRequest,
        ]);
    }

    /**
     * Download exported data
     */
    public function download(string $token)
    {
        $gdprRequest = GdprRequest::where('verification_token', $token)
            ->where('type', GdprRequest::TYPE_EXPORT)
            ->where('status', GdprRequest::STATUS_COMPLETED)
            ->firstOrFail();

        if (!$gdprRequest->export_file_path || !Storage::exists($gdprRequest->export_file_path)) {
            abort(404, 'Export file not found');
        }

        return Storage::download($gdprRequest->export_file_path, 'gdpr-export-' . $gdprRequest->id . '.json');
    }

    /**
     * Process export request
     */
    protected function processExport(GdprRequest $request): void
    {
        $request->update(['status' => GdprRequest::STATUS_PROCESSING]);
        $request->addLog('Starting data export');

        if ($request->user_id) {
            $user = \App\Models\User::findOrFail($request->user_id);
            $filePath = $this->exportService->exportUserData($user, $request);
        } elseif ($request->customer_id) {
            $customer = Customer::findOrFail($request->customer_id);
            $filePath = $this->exportService->exportCustomerData($customer, $request);
        } else {
            throw new \Exception('No user or customer associated with request');
        }

        $request->markAsCompleted($filePath);
        $this->sendExportReadyEmail($request);
    }

    /**
     * Process deletion request
     */
    protected function processDeletion(GdprRequest $request): void
    {
        $request->update(['status' => GdprRequest::STATUS_PROCESSING]);
        $request->addLog('Starting data deletion');

        if ($request->user_id) {
            $user = \App\Models\User::findOrFail($request->user_id);
            $canDelete = $this->deletionService->canDeleteUser($user);

            if (!$canDelete['can_delete']) {
                $request->markAsRejected(implode(' ', $canDelete['reasons']));
                return;
            }

            $this->deletionService->deleteUserData($user, $request);
        } elseif ($request->customer_id) {
            $customer = Customer::findOrFail($request->customer_id);
            $this->deletionService->deleteCustomerData($customer, $request);
        } else {
            throw new \Exception('No user or customer associated with request');
        }

        $this->sendDeletionCompleteEmail($request);
    }

    /**
     * Process anonymization request
     */
    protected function processAnonymization(GdprRequest $request): void
    {
        $request->update(['status' => GdprRequest::STATUS_PROCESSING]);
        $request->addLog('Starting data anonymization');

        if ($request->user_id) {
            $user = \App\Models\User::findOrFail($request->user_id);
            $this->anonymizationService->anonymizeUserData($user, $request);
        } elseif ($request->customer_id) {
            $customer = Customer::findOrFail($request->customer_id);
            $this->anonymizationService->anonymizeCustomerData($customer, $request);
        } else {
            throw new \Exception('No user or customer associated with request');
        }

        $this->sendAnonymizationCompleteEmail($request);
    }

    /**
     * Process rectification request
     */
    protected function processRectification(GdprRequest $request): void
    {
        // Rectification requests typically require manual review
        $request->update([
            'status' => GdprRequest::STATUS_PENDING,
        ]);
        $request->addLog('Rectification request requires manual review');
        $this->sendRectificationReceivedEmail($request);
    }

    /**
     * Send verification email
     */
    protected function sendVerificationEmail(GdprRequest $request): void
    {
        // TODO: Implement email sending
        // Mail::to($request->email)->send(new GdprVerificationMail($request));
    }

    /**
     * Send export ready email
     */
    protected function sendExportReadyEmail(GdprRequest $request): void
    {
        // TODO: Implement email sending
        // Mail::to($request->email)->send(new GdprExportReadyMail($request));
    }

    /**
     * Send deletion complete email
     */
    protected function sendDeletionCompleteEmail(GdprRequest $request): void
    {
        // TODO: Implement email sending
        // Mail::to($request->email)->send(new GdprDeletionCompleteMail($request));
    }

    /**
     * Send anonymization complete email
     */
    protected function sendAnonymizationCompleteEmail(GdprRequest $request): void
    {
        // TODO: Implement email sending
        // Mail::to($request->email)->send(new GdprAnonymizationCompleteMail($request));
    }

    /**
     * Send rectification received email
     */
    protected function sendRectificationReceivedEmail(GdprRequest $request): void
    {
        // TODO: Implement email sending
        // Mail::to($request->email)->send(new GdprRectificationReceivedMail($request));
    }
}
