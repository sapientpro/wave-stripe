<div class="p-8">

	@subscriber

        @include('theme::payments.' . strtolower(config('payment.vendor')) . '.settings.partials.invoices')

	@notsubscriber
		<p class="text-gray-600">When you subscribe to a plan, this is where you will be able to download your invoices.</p>
		<a href="{{ route('wave.settings', 'plans') }}" class="inline-flex self-start justify-center w-auto px-4 py-2 mt-5 text-sm font-medium text-white transition duration-150 ease-in-out border border-transparent rounded-md bg-wave-600 hover:bg-wave-500 focus:outline-none focus:border-wave-700 focus:shadow-outline-wave active:bg-wave-700">View Plans</a>
	@endsubscriber

</div>
