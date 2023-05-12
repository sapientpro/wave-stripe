@php
    $subscriptionName = auth()->user()->getCurrentSubscriptionName();
@endphp
<div class="flex flex-col">
    <h5 class="mb-2 text-xl font-semibold text-gray-700">Your active subscription is: <span class="font-normal underline">{{ auth()->user()->role->display_name }}</span></h5>
</div>

<hr class="my-8">

<div class="flex flex-col">
    <h5 class="mb-2 text-xl font-bold text-gray-700">Modify your Subscription</h5>
    <p>Click the button below to update your subscription or payment methods</p>
    <a href="{{ route('stripe.billing-portal') }}" class="inline-flex self-start justify-center w-auto px-4 py-2 mt-5 text-sm font-medium text-white transition duration-150 ease-in-out border border-transparent rounded-md checkout-update bg-wave-600 hover:bg-wave-500 focus:outline-none focus:border-wave-700 focus:shadow-outline-wave active:bg-wave-700">Update details</a>
</div>

@if (auth()->user()->subscription($subscriptionName)?->onGracePeriod())
    <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mt-6">
        <div class="flex">
            <div class="flex-shrink-0">
                <!-- Heroicon name: solid/exclamation -->
                <svg class="h-5 w-5 text-yellow-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                </svg>
            </div>
            <div class="ml-3">
                <p class="text-sm text-yellow-700">
                    Your plan is in grace period.
                    <a href="{{ route('stripe.billing-portal') }}" class="font-medium underline text-yellow-700 hover:text-yellow-600">
                        Resume your subscription here
                    </a>
                    <br>
                    <span>
                        You have <b>{{ auth()->user()->subscription($subscriptionName)->ends_at->diffInDays(now()) }} days left.</b>
                    </span>
                </p>
            </div>
        </div>
    </div>
@endif

