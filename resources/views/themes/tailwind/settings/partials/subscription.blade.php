<div class="p-8">
    @if(auth()->user()->hasRole('admin'))
        <p>This user is an admin user and therefore does not need a subscription</p>
    @else

        @include('theme::payments.' . strtolower(config('payment.vendor')) . '.settings.partials.subscription')

    @endif
</div>
<script>
	window.cancelClicked = function(){
		Alpine.store('confirmCancel').openModal();
	}
</script>
