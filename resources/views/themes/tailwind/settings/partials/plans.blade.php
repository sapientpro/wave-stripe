@php $plans = Wave\Plan::all() @endphp

@include('theme::payments.' . strtolower(config('payment.vendor')) . '.settings.partials.plans', ['plans' => $plans])
