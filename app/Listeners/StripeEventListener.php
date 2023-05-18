<?php

namespace App\Listeners;

use Wave\User;
use TCG\Voyager\Models\Role;
use Laravel\Cashier\Events\WebhookReceived;

class StripeEventListener
{
    const EVENT_SUBSCRIPTION_DELETED = 'customer.subscription.deleted';

    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     *  Handle received Stripe webhooks.
     * @param WebhookReceived $event
     * @return false|void
     */
    public function handle(WebhookReceived $event)
    {
        if ($event->payload['type'] !== self::EVENT_SUBSCRIPTION_DELETED) {
            return false;
        }

        $user = User::where('stripe_id', $event->payload['data']['object']['customer'])->first();
        if (!$user) {
            return false;
        }

        $cancelledRole = Role::query()->where('name', 'cancelled')->first();
        $user->role_id = $cancelledRole->id;
        $user->save();
    }
}
