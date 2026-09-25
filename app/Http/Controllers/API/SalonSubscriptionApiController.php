<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\SalonSubscription;
use App\Models\User;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class SalonSubscriptionApiController extends Controller
{
    /**
     * Vérifier le statut d'abonnement d'un appareil ou code
     */
    public function checkSubscription(Request $request)
    {
        $request->validate([
            'salon_code' => 'nullable|string|max:50',
            'modda_code' => 'nullable|string|max:50',
            'device_id'  => 'nullable|string|max:120',
        ]);

        try {
            $code = $request->input('salon_code') ?? $request->input('modda_code');
            $deviceId = $request->input('device_id');
            $user = auth('sanctum')->user() ?? auth()->user();

            $query = SalonSubscription::query();

            if (!empty($code)) {
                $query->bySalonCode($code);
            } elseif (!empty($deviceId)) {
                $query->byDeviceId($deviceId);
            } elseif ($user) {
                $query->where('user_id', $user->id);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'salon_code ou device_id manquant'
                ], 400);
            }

            $subscription = $query->first();

            if (!$subscription) {
                // Si l'utilisateur connecté existe et a un abonnement classique, synchroniser
                if ($user && method_exists($user, 'is_subscribe') && $user->is_subscribe) {
                    $activeLegacy = \Modules\Subscriptions\Models\Subscription::where('user_id', $user->id)
                        ->where('status', 'active')
                        ->where('end_date', '>', now())
                        ->first();

                    if ($activeLegacy) {
                        $subscription = SalonSubscription::create([
                            'salon_code'              => $code ? strtoupper(trim($code)) : SalonSubscription::generateSalonCode(),
                            'device_id'               => $deviceId,
                            'user_id'                 => $user->id,
                            'device_name'             => $user->first_name . ' ' . $user->last_name,
                            'install_date'            => now(),
                            'subscription_start_date' => $activeLegacy->start_date ?? now(),
                            'subscription_end_date'   => $activeLegacy->end_date,
                            'status'                  => 'active',
                            'subscription_type'       => $activeLegacy->type ?? 'monthly',
                            'amount_paid'             => $activeLegacy->amount ?? 0,
                            'is_active'               => true,
                        ]);
                    }
                }

                if (!$subscription) {
                    return response()->json([
                        'success'            => false,
                        'message'            => 'Abonnement non trouvé',
                        'needs_registration' => !empty($deviceId) || !empty($code),
                        'subscription'       => null,
                    ], 200);
                }
            }

            // Associer l'appareil ou l'utilisateur s'ils manquent
            if (empty($subscription->device_id) && !empty($deviceId)) {
                $subscription->device_id = $deviceId;
            }
            if (empty($subscription->user_id) && $user) {
                $subscription->user_id = $user->id;
            }

            $subscription->updateLastActivity();

            return response()->json([
                'success' => true,
                'subscription' => [
                    'salon_code'            => $subscription->salon_code,
                    'modda_code'            => $subscription->salon_code, // Alias rétrocompatible
                    'device_id'             => $subscription->device_id,
                    'device_name'           => $subscription->device_name,
                    'status'                => $subscription->status,
                    'is_active'             => $subscription->isActive(),
                    'is_trial_expired'      => $subscription->isTrialExpired(),
                    'remaining_days'        => $subscription->getRemainingDays(),
                    'trial_end_date'        => $subscription->trial_end_date?->format('Y-m-d H:i:s'),
                    'subscription_end_date' => $subscription->subscription_end_date?->format('Y-m-d H:i:s'),
                    'subscription_type'     => $subscription->subscription_type,
                    'install_date'          => $subscription->install_date?->format('Y-m-d H:i:s'),
                    'last_activity'         => $subscription->last_activity?->format('Y-m-d H:i:s')
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('[checkSubscription] Erreur: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la vérification',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Enregistrer un nouvel appareil avec période d'essai ou associer un code
     */
    public function registerDevice(Request $request)
    {
        $request->validate([
            'salon_code'  => 'nullable|string|max:50',
            'modda_code'  => 'nullable|string|max:50',
            'device_id'   => 'nullable|string|max:120',
            'device_name' => 'nullable|string|max:190',
            'app_version' => 'nullable|string|max:50',
            'device_info' => 'nullable|array'
        ]);

        try {
            $code = $request->input('salon_code') ?? $request->input('modda_code');
            $deviceId = $request->input('device_id');
            $user = auth('sanctum')->user() ?? auth()->user();

            $existing = null;
            if (!empty($code)) {
                $existing = SalonSubscription::bySalonCode($code)->first();
            }
            if (!$existing && !empty($deviceId)) {
                $existing = SalonSubscription::byDeviceId($deviceId)->first();
            }

            if ($existing) {
                if ($deviceId && empty($existing->device_id)) {
                    $existing->device_id = $deviceId;
                }
                if ($user && empty($existing->user_id)) {
                    $existing->user_id = $user->id;
                }
                if ($request->device_name) {
                    $existing->device_name = $request->device_name;
                }
                $existing->updateLastActivity();

                return response()->json([
                    'success'      => true,
                    'message'      => 'Appareil déjà enregistré',
                    'subscription' => [
                        'salon_code'            => $existing->salon_code,
                        'modda_code'            => $existing->salon_code,
                        'device_id'             => $existing->device_id,
                        'device_name'           => $existing->device_name,
                        'status'                => $existing->status,
                        'is_active'             => $existing->isActive(),
                        'is_trial_expired'      => $existing->isTrialExpired(),
                        'remaining_days'        => $existing->getRemainingDays(),
                        'trial_end_date'        => $existing->trial_end_date?->format('Y-m-d H:i:s'),
                        'subscription_end_date' => $existing->subscription_end_date?->format('Y-m-d H:i:s'),
                        'subscription_type'     => $existing->subscription_type,
                        'install_date'          => $existing->install_date?->format('Y-m-d H:i:s')
                    ]
                ]);
            }

            // Créer un nouvel enregistrement avec essai de 3 jours
            $installDate = Carbon::now();
            $trialEndDate = $installDate->copy()->addDays(3);
            $finalCode = !empty($code) ? strtoupper(trim($code)) : SalonSubscription::generateSalonCode();

            $subscription = SalonSubscription::create([
                'salon_code'        => $finalCode,
                'device_id'         => $deviceId,
                'user_id'           => $user?->id,
                'device_name'       => $request->device_name ?? ($user ? ($user->first_name . ' ' . $user->last_name) : 'Mobile Device'),
                'app_version'       => $request->app_version ?? '1.0.0',
                'install_date'      => $installDate,
                'trial_end_date'    => $trialEndDate,
                'status'            => 'trial',
                'device_info'       => $request->device_info,
                'last_activity'     => $installDate,
                'is_active'         => true,
                'notes'             => 'Enregistré via application mobile'
            ]);

            return response()->json([
                'success'      => true,
                'message'      => 'Appareil enregistré avec succès',
                'subscription' => [
                    'salon_code'       => $subscription->salon_code,
                    'modda_code'       => $subscription->salon_code,
                    'device_id'        => $subscription->device_id,
                    'device_name'      => $subscription->device_name,
                    'status'           => $subscription->status,
                    'is_active'        => $subscription->isActive(),
                    'is_trial_expired' => $subscription->isTrialExpired(),
                    'remaining_days'   => $subscription->getRemainingDays(),
                    'trial_end_date'   => $subscription->trial_end_date?->format('Y-m-d H:i:s'),
                    'install_date'     => $subscription->install_date?->format('Y-m-d H:i:s')
                ]
            ], 201);

        } catch (\Exception $e) {
            Log::error('[registerDevice] Erreur: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'enregistrement',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Prolonger l'abonnement par code SALON
     */
    public function extendSubscriptionByCode(Request $request)
    {
        $request->validate([
            'salon_code'        => 'nullable|string|max:50',
            'modda_code'        => 'nullable|string|max:50',
            'days'              => 'required|integer|min:1|max:3650',
            'subscription_type' => 'required|string',
            'amount_paid'       => 'nullable|numeric|min:0',
            'payment_method'    => 'nullable|string|max:100',
            'payment_reference' => 'nullable|string|max:255',
            'notes'             => 'nullable|string|max:1000'
        ]);

        try {
            $code = strtoupper(trim($request->input('salon_code') ?? $request->input('modda_code')));

            $subscription = SalonSubscription::bySalonCode($code)->first();

            if (!$subscription) {
                // Créer l'abonnement s'il n'existe pas encore
                $now = Carbon::now();
                $subscription = SalonSubscription::create([
                    'salon_code'              => $code,
                    'status'                  => 'active',
                    'install_date'            => $now,
                    'trial_end_date'          => $now,
                    'subscription_start_date' => $now,
                    'subscription_end_date'   => $now->copy()->addDays($request->days),
                    'subscription_type'       => $request->subscription_type,
                    'amount_paid'             => $request->amount_paid ?? 0,
                    'payment_method'          => $request->payment_method,
                    'payment_reference'       => $request->payment_reference,
                    'notes'                   => $request->notes ?? 'Créé et prolongé par l\'administrateur SALON',
                    'is_active'               => true,
                    'last_activity'           => $now,
                ]);
            } else {
                $subscription->extendSubscription(
                    (int) $request->days,
                    $request->subscription_type,
                    (float) ($request->amount_paid ?? 0),
                    $request->payment_method,
                    $request->payment_reference
                );

                if ($request->notes) {
                    $subscription->notes = $request->notes;
                    $subscription->save();
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Abonnement prolongé avec succès',
                'subscription' => [
                    'salon_code'            => $subscription->salon_code,
                    'modda_code'            => $subscription->salon_code,
                    'device_name'           => $subscription->device_name,
                    'status'                => $subscription->status,
                    'is_active'             => $subscription->isActive(),
                    'remaining_days'        => $subscription->getRemainingDays(),
                    'subscription_end_date' => $subscription->subscription_end_date?->format('Y-m-d H:i:s'),
                    'subscription_type'     => $subscription->subscription_type,
                    'amount_paid'           => (float) $subscription->amount_paid
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('[extendSubscriptionByCode] Erreur: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la prolongation',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Annuler un abonnement
     */
    public function cancelSubscription(Request $request)
    {
        $request->validate([
            'salon_code' => 'nullable|string|max:50',
            'modda_code' => 'nullable|string|max:50',
            'device_id'  => 'nullable|string|max:120',
            'reason'     => 'nullable|string|max:500'
        ]);

        try {
            $code = $request->input('salon_code') ?? $request->input('modda_code');
            $deviceId = $request->input('device_id');

            $query = SalonSubscription::query();
            if ($code) {
                $query->bySalonCode($code);
            } elseif ($deviceId) {
                $query->byDeviceId($deviceId);
            } else {
                return response()->json(['success' => false, 'message' => 'Identifiant manquant'], 400);
            }

            $subscription = $query->first();

            if (!$subscription) {
                return response()->json(['success' => false, 'message' => 'Abonnement non trouvé'], 404);
            }

            if ($request->reason) {
                $subscription->notes = ($subscription->notes ?? '') . "\nAnnulation: " . $request->reason;
            }

            $subscription->cancelSubscription();

            return response()->json([
                'success' => true,
                'message' => 'Abonnement annulé avec succès'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'annulation',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Lister tous les abonnements pour le tableau de bord admin
     */
    public function listSubscriptions(Request $request)
    {
        try {
            $query = SalonSubscription::query();

            if ($request->has('status') && $request->status !== 'all' && !empty($request->status)) {
                $query->where('status', $request->status);
            }

            if ($request->has('active_only')) {
                $query->active();
            }

            if ($request->has('expired_only')) {
                $query->expired();
            }

            $perPage = (int) $request->get('per_page', 50);
            $subscriptions = $query->orderBy('updated_at', 'desc')->paginate($perPage);

            $subscriptions->through(function ($sub) {
                return [
                    'id'                    => $sub->id,
                    'salon_code'            => $sub->salon_code,
                    'modda_code'            => $sub->salon_code,
                    'device_id'             => $sub->device_id,
                    'device_name'           => $sub->device_name ?: ($sub->user ? ($sub->user->first_name . ' ' . $sub->user->last_name) : 'Appareil mobile'),
                    'app_version'           => $sub->app_version,
                    'status'                => $sub->status,
                    'is_active'             => $sub->isActive(),
                    'remaining_days'        => $sub->getRemainingDays(),
                    'subscription_type'     => $sub->subscription_type,
                    'amount_paid'           => (float) $sub->amount_paid,
                    'install_date'          => $sub->install_date?->format('Y-m-d H:i:s'),
                    'trial_end_date'        => $sub->trial_end_date?->format('Y-m-d H:i:s'),
                    'subscription_end_date' => $sub->subscription_end_date?->format('Y-m-d H:i:s'),
                    'last_activity'         => $sub->last_activity?->format('Y-m-d H:i:s') ?? $sub->updated_at?->format('Y-m-d H:i:s'),
                    'notes'                 => $sub->notes,
                    'created_at'            => $sub->created_at?->format('Y-m-d H:i:s')
                ];
            });

            return response()->json([
                'success'       => true,
                'subscriptions' => $subscriptions
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des abonnements',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Statistiques globales des abonnements pour les cartes
     */
    public function getStats()
    {
        try {
            $totalCount = SalonSubscription::count();
            $activeCount = SalonSubscription::active()->count();
            $trialCount = SalonSubscription::where('status', 'trial')->where(function($q) {
                $q->whereNull('trial_end_date')->orWhere('trial_end_date', '>', Carbon::now());
            })->count();
            $expiredCount = SalonSubscription::expired()->count();
            $cancelledCount = SalonSubscription::where('status', 'cancelled')->count();
            $totalRevenue = (float) SalonSubscription::sum('amount_paid');
            $payingUsers = SalonSubscription::where('amount_paid', '>', 0)->count();
            $arpu = $payingUsers > 0 ? round($totalRevenue / $payingUsers, 2) : 0;

            $stats = [
                'total_subscriptions'     => $totalCount,
                'active_subscriptions'    => $activeCount,
                'trial_subscriptions'     => $trialCount,
                'expired_subscriptions'   => $expiredCount,
                'cancelled_subscriptions' => $cancelledCount,
                'total_revenue'           => $totalRevenue,
                'average_revenue_per_user'=> $arpu,
            ];

            return response()->json([
                'success' => true,
                'stats'   => $stats
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des statistiques',
                'error'   => $e->getMessage()
            ], 500);
        }
    }
}
