<?php

namespace App\Traits;

use Wave\PaddleSubscription;
use Wave\Plan;

trait SubscriptionsTrait
{
    protected string $paymentVendor;

    public function __construct()
    {
        parent::__construct();
        $this->paymentVendor = config('payment.vendor');
    }

    public function subscribed(string $planName = 'default', string $price = null): bool
    {
        switch ($this->paymentVendor) {
            case 'stripe':
                return $this->stripeSubscribed($planName, $price);
            default:
                return $this->defaultSubscribed($planName);
        }
    }

    protected function stripeSubscribed(string $planName, string $price = null): bool
    {
        $subscription = $this->subscription($planName);

        if (!$subscription || !$subscription->valid() || $subscription->ended()) {
            return false;
        }

        return !$price || $subscription->hasPrice($price);
    }

    protected function defaultSubscribed(string $planName): bool
    {
        $plan = Plan::where('slug', $planName)->first();

        if (isset($plan->default) && $plan->default && $this->hasRole('admin')) {
            return true;
        }

        if (isset($plan->slug) && $this->hasRole($plan->slug)) {
            return true;
        }

        return false;
    }

    public function subscription(string $name = 'default')
    {
        switch ($this->paymentVendor) {
            case 'stripe':
                return $this->stripeSubscription($name);
            default:
                return $this->defaultSubscription();
        }
    }

    protected function stripeSubscription(string $name)
    {
        return $this->subscriptions->where('name', $name)->first();
    }

    protected function defaultSubscription()
    {
        return $this->hasOne(PaddleSubscription::class);
    }
}
