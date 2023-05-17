<?php

namespace App\Traits;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
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

    public function getInvoices(): mixed
    {
        return match ($this->getPaymentVendor()) {
            'stripe' => $this->getStripeInvoices(),
            default => $this->getDefaultInvoices(),
        };
    }

    protected function getStripeInvoices(): Collection
    {
        return $this->invoices();
    }

    protected function getDefaultInvoices(): mixed
    {
        $invoices = [];

        if(isset($this->subscription->subscription_id)){
            $paddle_vendors_url = (config('wave.paddle.env') == 'sandbox') ? 'https://sandbox-vendors.paddle.com/api' : 'https://vendors.paddle.com/api';
            $response = Http::post($paddle_vendors_url . '/2.0/subscription/payments', [
                'vendor_id' => config('wave.paddle.vendor'),
                'vendor_auth_code' => config('wave.paddle.auth_code'),
                'subscription_id' => $this->subscription->subscription_id,
                'is_paid' => 1
            ]);

            $invoices = json_decode($response->body());
        }

        return $invoices;
    }
}
