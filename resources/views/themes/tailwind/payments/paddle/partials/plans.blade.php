<div data-plan="{{ $plan->plan_id }}" class="inline-flex items-center justify-center w-full px-4 py-4 text-base font-semibold text-white transition duration-150 ease-in-out @if($plan->default){{ ' bg-gradient-to-r from-wave-600 to-indigo-500 hover:from-wave-500 hover:to-indigo-400' }}@else{{ 'bg-gray-800 hover:bg-gray-700 active:bg-gray-900 focus:border-gray-900 focus:shadow-outline-gray' }}@endif border border-transparent cursor-pointer rounded-b-md checkout focus:outline-none disabled:opacity-25">
    @subscribed($plan->slug)
        You are subscribed to this plan
    @notsubscribed
    @subscriber
        Switch Plans
    @notsubscriber
        Get Started
    @endsubscriber
    @endsubscribed
</div>
