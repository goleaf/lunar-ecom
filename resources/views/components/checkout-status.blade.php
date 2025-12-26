@props(['status'])

@php
    use App\Helpers\CheckoutHelper;
    
    $isLocked = $status['locked'] ?? false;
    $state = $status['state'] ?? null;
    $stateName = $status['state_name'] ?? CheckoutHelper::getStateName($state ?? '');
    $expiresAt = $status['expires_at'] ?? null;
@endphp

@if($isLocked)
    <div class="checkout-status-alert alert alert-info" role="alert">
        <div class="d-flex align-items-center">
            <svg class="me-2" width="20" height="20" fill="currentColor" viewBox="0 0 16 16">
                <path d="M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16zm.93-9.412-1 4.705c-.07.34.029.533.304.533.194 0 .487-.07.686-.246l-.088.416c-.287.346-.92.598-1.465.598-.703 0-1.002-.422-.808-1.203l1.001-4.705z"/>
                <path d="M8 0a8 8 0 1 0 0 16A8 8 0 0 0 8 0zM1.5 8a6.5 6.5 0 1 1 13 0 6.5 6.5 0 0 1-13 0z"/>
            </svg>
            <div class="flex-grow-1">
                <strong>Checkout in Progress</strong>
                <p class="mb-0">
                    Current phase: <strong>{{ $stateName }}</strong>
                    @if($expiresAt)
                        <br>
                        <small>Expires: {{ \Carbon\Carbon::parse($expiresAt)->diffForHumans() }}</small>
                    @endif
                </p>
            </div>
            @if($status['can_resume'] ?? false)
                <button type="button" class="btn btn-sm btn-outline-primary" onclick="resumeCheckout()">
                    Resume Checkout
                </button>
            @endif
        </div>
    </div>

    <script>
        function resumeCheckout() {
            // Implement resume checkout logic
            window.location.href = '{{ route("frontend.checkout.index") }}';
        }
    </script>
@endif



