<?php

namespace Wave\Http\Controllers;

use Illuminate\Http\Request;
use Laravel\Cashier\Http\Controllers\WebhookController as StripeWebhookController;

class WebhookController extends StripeWebhookController
{
    public function handle(Request $request)
    {
        $this->handleWebhook($request);
    }
}
