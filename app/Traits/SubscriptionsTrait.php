<?php

namespace App\Traits;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\DB;
use Laravel\Cashier\Subscription;
use Wave\PaddleSubscription;
use Wave\Plan;

trait SubscriptionsTrait
{
    protected ?string $paymentVendor = null;

    public function getPaymentVendor(): string
    {
        if ($this->paymentVendor === null) {
            $this->paymentVendor = config('payment.vendor');
        }

        return $this->paymentVendor;
    }


    public function subscribed(string $planName = 'default', string $price = null): bool
    {
        return match ($this->getPaymentVendor()) {
            'stripe' => $this->stripeSubscribed($planName, $price),
            default => $this->defaultSubscribed($planName),
        };
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

    public function subscription(string $name = 'default'): HasOne|Subscription|null
    {
        return match ($this->getPaymentVendor()) {
            'stripe' => $this->stripeSubscription($name),
            default => $this->defaultSubscription(),
        };
    }

    protected function stripeSubscription(string $name): ?Subscription
    {
        return $this->subscriptions->where('name', $name)->first();
    }

    protected function defaultSubscription(): HasOne
    {
        return $this->hasOne(PaddleSubscription::class);
    }

    public function getCurrentSubscriptionName(): string
    {
        return match ($this->getPaymentVendor()) {
            'stripe' => $this->getCurrentStripeSubscriptionName(),
            default => $this->getCurrentDefaultSubscriptionName(),
        };
    }

    public function getCurrentStripeSubscriptionName(): string
    {
        $today = Carbon::today();

        $subscription = DB::table('subscriptions')
            ->where('user_id', '=', $this->id)
            ->where(function ($query) use ($today) {
                $query->where('ends_at', '>', $today) // exclude ended subscriptions
                ->orWhereNull('ends_at'); // include subscriptions with no end date
            })
            ->first();

        return $subscription?->name ?? '';
    }

    public function getCurrentDefaultSubscriptionName(): string
    {
        return $this->role->display_name;
    }
}
