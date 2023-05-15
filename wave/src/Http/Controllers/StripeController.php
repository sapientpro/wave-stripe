<?php

namespace Wave\Http\Controllers;

use App\Services\StripeService;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Stripe\Exception\ApiErrorException;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class StripeController extends Controller
{
    /**
     * @param StripeService $stripeService
     */
    public function __construct(
        private readonly StripeService $stripeService
    )
    {
    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @throws ApiErrorException
     */
    public function postCreateCheckoutSession(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'planId' => 'required|exists:plans,plan_id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $session = $this->stripeService->createCheckoutSession($request);

        return response()->json($session, 200);
    }

    /**
     * Handle the success Stripe Checkout
     * @param Request $request
     * @return Application|Factory|View
     * @throws ApiErrorException
     */
    public function postSuccessCheckout(Request $request): Application|Factory|View
    {
        $user = $this->stripeService->handleSuccessCheckout($request);

        if ($user) {
            Auth::login($user);
            session()->flash('complete');
            return view('theme::welcome');
        }

        return abort(401);
    }

    public function postErrorCheckout(Request $request): RedirectResponse
    {
        Log::error('Stripe checkout failed.', ['request' => $request->all()]);

        return redirect()
            ->back()
            ->with(['message' => 'There was an error processing your payment. Please try again.', 'message_type' => 'warning']);
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function getBillingPortal(Request $request): RedirectResponse
    {
        return $request->user()->redirectToBillingPortal(url()->previous());
    }
}
