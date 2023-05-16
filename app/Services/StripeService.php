<?php

namespace App\Services;

use App\Repositories\UserRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Laravel\Cashier\Subscription;
use Stripe\Checkout\Session;
use Stripe\Customer;
use Stripe\Exception\ApiErrorException;
use Stripe\StripeClient;
use Stripe\Subscription as StripeSubscription;
use Stripe\SubscriptionItem;
use TCG\Voyager\Models\Role;
use Wave\Plan;
use Wave\User;

class StripeService
{
    const DEFAULT_SUBSCRIPTION_NAME = 'default';

    public function __construct(
        private readonly UserRepository $userRepository
    )
    {
    }

    /**
     * @param Request $request
     * @return Session
     * @throws ApiErrorException
     */
    public function createCheckoutSession(Request $request): Session
    {
        $stripe = new StripeClient(config('payment.stripe.secret'));
        $plan = Plan::query()->where('plan_id', '=', $request->get('planId'))->first();
        $sessionData = [
            'success_url' => route('stripe.success-checkout') . "?session_id={CHECKOUT_SESSION_ID}",
            'cancel_url' => route('stripe.error-checkout'),
            'line_items' => [
                [
                    'price' => $plan->plan_id,
                    'quantity' => 1,
                ],
            ],
            'mode' => 'subscription',
            'tax_id_collection' => [
                'enabled' => config('payment.stripe.calculate_taxes')
            ],
            'allow_promotion_codes' => config('payment.stripe.allow_promo_codes'),
            'locale' => "auto",
            'metadata' => [
                'name' => $plan->slug,
            ],
        ];

        if ($plan->trial_days > 0) {
            $sessionData['subscription_data'] = [
                'trial_period_days' => $plan->trial_days,
            ];
        }

        if (auth()->check() && auth()->user()->stripe_id) {
            $sessionData['customer'] = auth()->user()->stripe_id;
        }

        if (auth()->check() && !auth()->user()->stripe_id) {
            $sessionData['customer_email'] = auth()->user()->email;
        }

        return $stripe->checkout->sessions->create($sessionData);
    }

    /**
     * @param Request $request
     * @return User|null
     * @throws ApiErrorException
     */
    public function handleSuccessCheckout(Request $request): ?User
    {
        if (!$request->get('session_id')) {
            abort(401);
        }
        $stripe = new StripeClient(config('payment.stripe.secret'));

        $session = $stripe->checkout->sessions->retrieve($request->get('session_id'));
        $customer = $stripe->customers->retrieve($session->customer);
        if (!$session || !$customer) {
            abort(401);
        }

        $subscription = $stripe->subscriptions->retrieve($session->subscription);

        $user = $this->createUserFromCheckout($customer);
        $name = $session->metadata?->name ?? self::DEFAULT_SUBSCRIPTION_NAME;
        $this->createStripeSubscription($user, $subscription, $name);

        // Update User role
        $plan = Plan::where('plan_id', $subscription->plan->id)->first();
        // add associated role to user
        $user->role_id = $plan->role_id;
        $user->save();

        return $user;
    }

    private function createUserFromCheckout(Customer $customer): User
    {
        $user = $this->userRepository->getByEmail($customer->email);
        if ($user) {
            if (!$user->stripe_id) {
                $this->userRepository->updateStripeId($user, $customer->id);
            }
            session()->flash('existing_customer');
            return $user;
        };

        return $this->userRepository->createFromStripeCustomer($customer);
    }

    private function createStripeSubscription($user, StripeSubscription $stripeSubscription, string $name = self::DEFAULT_SUBSCRIPTION_NAME): Subscription|bool
    {
        if (!$user->hasDefaultPaymentMethod()) {
            $user->updateDefaultPaymentMethod($stripeSubscription->default_payment_method);
        }

        if ($user->subscriptions()->count()) {
            return false;
        }

        // Manually add subscription to our database. The Stripe subscription has already been added throw checkout
        /** @var SubscriptionItem $firstItem */
        $firstItem = $stripeSubscription->items->first();
        $isSinglePrice = $stripeSubscription->items->count() === 1;

        /** @var Subscription $subscription */
        $subscription = $user->subscriptions()->create([
            'name' => $name,
            'stripe_id' => $stripeSubscription->id,
            'stripe_status' => $stripeSubscription->status,
            'stripe_price' => $isSinglePrice ? $firstItem->price->id : null,
            'quantity' => $isSinglePrice ? $firstItem->quantity : null,
            'trial_ends_at' => $stripeSubscription->trial_end,
            'ends_at' => null,
        ]);

        /** @var SubscriptionItem $item */
        foreach ($stripeSubscription->items as $item) {
            $subscription->items()->create([
                'stripe_id' => $item->id,
                'stripe_product' => $item->price->product,
                'stripe_price' => $item->price->id,
                'quantity' => $item->quantity ?? null,
            ]);
        }

        return $subscription;
    }
}
