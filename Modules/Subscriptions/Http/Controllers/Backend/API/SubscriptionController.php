<?php

namespace Modules\Subscriptions\Http\Controllers\Backend\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Modules\Subscriptions\Http\Requests\SubscriptionRequest;
use Modules\Subscriptions\Models\Plan;
use Modules\Subscriptions\Models\Subscription;
use Modules\Subscriptions\Models\SubscriptionTransactions;
use Modules\Subscriptions\Trait\SubscriptionTrait;
use Modules\Subscriptions\Transformers\SubscriptionResource;

class SubscriptionController extends Controller
{
    use SubscriptionTrait;

    /**
     * Display a listing of the resource.
     *
     * @return Renderable
     */
    public function saveSubscriptionDetails(SubscriptionRequest $request)
    {
        $user_id = $request->user_id ? $request->user_id : auth()->id();

        $user = User::where('id', $user_id)->first();

        $timezone = date_default_timezone_set($this->getTimeZone());

        $get_existing_plan = $this->get_user_active_plan($user_id);

        $active_plan_left_days = 0;

        //set Default Status

        $status = config('constant.SUBSCRIPTION_STATUS.PENDING');

        //start date

        $start_date = date('Y-m-d H:i:s');

        if ($get_existing_plan) {
            $active_plan_left_days = $this->check_days_left_plan($get_existing_plan);
            if ($request->identifier != $get_existing_plan->identifier) {
                $get_existing_plan->update([
                    'status' => config('constant.SUBSCRIPTION_STATUS.INACTIVE'),
                ]);
                $get_existing_plan->save();
            }
        }

        $plan = Plan::where('id', $request->plan_id)->first();

        //get end date

        $end_date = $this->get_plan_expiration_date($start_date, $plan['type'], $active_plan_left_days, $plan['duration']);

        $subscribed_plan_data = [

            'plan_id' => $request->plan_id,
            'user_id' => $user_id,
            'start_date' => $start_date,
            'end_date' => $end_date,
            'status' => $status,
            'amount' => $plan['amount'],
            'name' => $plan['name'],
            'identifier' => $plan['identifier'],
            'type' => $plan['type'],
            'duration' => $plan['duration'],
            'plan_type' => $plan['planlimitation'],

        ];

        $result = Subscription::create($subscribed_plan_data);

        if ($result) {
            $payment_data = [
                'subscriptions_id' => $result->id,
                'user_id' => $result->user_id,
                'amount' => $result->amount,
                'payment_status' => $request->payment_status,
                'payment_type' => $request->payment_type,
            ];
            $payment = SubscriptionTransactions::create($payment_data);

            if ($payment->payment_status == 'paid') {
                $result->status = config('constant.SUBSCRIPTION_STATUS.ACTIVE');
                $result->payment_id = $payment->id;
                $result->save();
                $user->is_subscribe = 1;
                $user->save();
                $message = __('messages.payment_completed');
            }
        }

        $items = new SubscriptionResource($result);

        $response = [

            'data' => $items,
        ];

        return $this->sendResponse($response, __('messages.user_subscribe'));
    }

    public function getUserSubscriptionHistroy()
    {
        $user_id = auth()->id();

        return $this->sendResponse(SubscriptionResource::collection(Subscription::where('user_id', $user_id)->get()), __('messages.user_subscribe_history'));
    }

    /**
     * GET /api/v1/subscription-status
     * Retourne le statut d'abonnement de l'utilisateur connecté.
     * Appelé par l'app mobile au démarrage du HomeFragment.
     */
    public function subscriptionStatus(Request $request)
    {
        try {
            $user_id = auth()->id();

            // On cherche directement dans le modèle Eloquent (pas via le Trait qui retourne un Resource)
            $activePlan = Subscription::where('user_id', $user_id)
                ->where('status', config('constant.SUBSCRIPTION_STATUS.ACTIVE', 'active'))
                ->first();

            if (! $activePlan) {
                return response()->json([
                    'status'         => true,
                    'is_subscribed'  => false,
                    'is_trial'       => false,
                    'plan_name'      => null,
                    'days_remaining' => 0,
                    'end_date'       => null,
                    'message'        => 'Aucun abonnement actif.',
                ]);
            }

            // Calcul des jours restants
            $daysRemaining = 0;
            if ($activePlan->end_date) {
                $endDate       = new \Carbon\Carbon($activePlan->end_date);
                $now           = \Carbon\Carbon::now();
                $daysRemaining = $endDate->gt($now) ? (int) $now->diffInDays($endDate) : 0;
            }

            $planStatus = strtolower($activePlan->status ?? '');
            $isTrial    = false;

            return response()->json([
                'status'          => true,
                'is_subscribed'   => true,
                'is_trial'        => $isTrial,
                'plan_name'       => $activePlan->name,
                'plan_identifier' => $activePlan->identifier,
                'days_remaining'  => $daysRemaining,
                'end_date'        => $activePlan->end_date,
                'subscription_id' => $activePlan->id,
            ]);
        } catch (\Exception $e) {
            // Ne jamais retourner 500 à l'app mobile — on log et on renvoie un statut neutre
            \Log::error('[subscription-status] Erreur: ' . $e->getMessage());
            return response()->json([
                'status'         => true,
                'is_subscribed'  => false,
                'is_trial'       => false,
                'plan_name'      => null,
                'days_remaining' => 0,
                'end_date'       => null,
                'message'        => 'Statut indisponible temporairement.',
            ]);
        }
    }

    public function cancelSubscription(Request $request)
    {
        $user_id = $request->user_id ? $request->user_id : auth()->id();
        $subscription_plan_id = $request->id;

        $user_subscription = Subscription::where('id', $subscription_plan_id)->where('user_id', $user_id)->first();

        $user = User::where('id', $user_id)->first();

        if ($user_subscription) {
            $user_subscription->status = config('constant.SUBSCRIPTION_STATUS.INACTIVE');
            $user_subscription->save();
            $user->is_subscribe = 0;
            $user->save();
        }

        $message = __('messages.subscribe_cancel');

        return $this->sendResponse($subscription_plan_id, $message);
    }
}
