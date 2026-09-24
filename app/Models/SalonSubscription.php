<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class SalonSubscription extends Model
{
    use HasFactory;

    protected $table = 'salon_subscriptions';

    protected $fillable = [
        'salon_code',
        'device_id',
        'user_id',
        'device_name',
        'app_version',
        'install_date',
        'trial_end_date',
        'subscription_start_date',
        'subscription_end_date',
        'status',
        'subscription_type',
        'amount_paid',
        'payment_method',
        'payment_reference',
        'device_info',
        'last_activity',
        'notes',
        'is_active',
    ];

    protected $casts = [
        'install_date' => 'datetime',
        'trial_end_date' => 'datetime',
        'subscription_start_date' => 'datetime',
        'subscription_end_date' => 'datetime',
        'last_activity' => 'datetime',
        'device_info' => 'array',
        'amount_paid' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Vérifier si l'abonnement est actif
     */
    public function isActive(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        $now = Carbon::now();

        switch ($this->status) {
            case 'trial':
                return $this->trial_end_date ? $now->lessThan($this->trial_end_date) : false;
            case 'active':
                return $this->subscription_end_date === null || $now->lessThan($this->subscription_end_date);
            case 'expired':
            case 'cancelled':
                return false;
            default:
                return false;
        }
    }

    /**
     * Vérifier si l'essai est expiré
     */
    public function isTrialExpired(): bool
    {
        return $this->status === 'trial' && $this->trial_end_date && Carbon::now()->greaterThan($this->trial_end_date);
    }

    /**
     * Obtenir le nombre de jours restants
     */
    public function getRemainingDays(): int
    {
        $now = Carbon::now();
        $endDate = $this->status === 'trial' ? $this->trial_end_date : $this->subscription_end_date;

        if ($endDate === null) {
            return -1; // Illimité
        }

        return max(0, (int) $now->diffInDays($endDate, false));
    }

    /**
     * Prolonger l'abonnement
     */
    public function extendSubscription(int $days, string $type = 'monthly', float|int|string $amount = 0, ?string $paymentMethod = null, ?string $paymentReference = null): bool
    {
        $now = Carbon::now();

        if ($this->status === 'trial' || empty($this->subscription_end_date) || Carbon::parse($this->subscription_end_date)->isPast()) {
            $this->subscription_start_date = $now;
            $this->subscription_end_date = $now->copy()->addDays($days);
            $this->status = 'active';
        } else {
            // Prolonger à partir de la date de fin existante
            $this->subscription_end_date = Carbon::parse($this->subscription_end_date)->addDays($days);
            $this->status = 'active';
        }

        $this->is_active = true;
        $this->subscription_type = $type;
        $this->amount_paid = (float)($this->amount_paid ?? 0) + $amount;
        if ($paymentMethod) $this->payment_method = $paymentMethod;
        if ($paymentReference) $this->payment_reference = $paymentReference;
        $this->last_activity = $now;

        $saved = $this->save();

        // Si associé à un utilisateur, activer aussi son statut
        if ($saved && $this->user_id) {
            User::where('id', $this->user_id)->update(['is_subscribe' => 1]);
        }

        return $saved;
    }

    /**
     * Annuler l'abonnement
     */
    public function cancelSubscription(): bool
    {
        $this->status = 'cancelled';
        $this->is_active = false;
        $this->last_activity = Carbon::now();

        $saved = $this->save();

        if ($saved && $this->user_id) {
            User::where('id', $this->user_id)->update(['is_subscribe' => 0]);
        }

        return $saved;
    }

    /**
     * Mettre à jour la dernière activité
     */
    public function updateLastActivity(): bool
    {
        $this->last_activity = Carbon::now();
        return $this->save();
    }

    /**
     * Scopes pour requêtes
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->where(function ($sub) {
                    $sub->where('status', 'active')
                        ->where(function ($dateQ) {
                            $dateQ->whereNull('subscription_end_date')
                                  ->orWhere('subscription_end_date', '>', Carbon::now());
                        });
                })->orWhere(function ($sub) {
                    $sub->where('status', 'trial')
                        ->where('trial_end_date', '>', Carbon::now());
                });
            });
    }

    public function scopeExpired($query)
    {
        return $query->where(function ($q) {
            $q->where('status', 'expired')
                ->orWhere(function ($sub) {
                    $sub->where('status', 'trial')
                        ->where('trial_end_date', '<=', Carbon::now());
                })
                ->orWhere(function ($sub) {
                    $sub->where('status', 'active')
                        ->whereNotNull('subscription_end_date')
                        ->where('subscription_end_date', '<=', Carbon::now());
                });
        });
    }

    public function scopeBySalonCode($query, $code)
    {
        return $query->where(DB::raw('UPPER(salon_code)'), strtoupper(trim($code)));
    }

    public function scopeByDeviceId($query, $deviceId)
    {
        return $query->where('device_id', $deviceId);
    }

    /**
     * Générer un code SALON unique au format SL-XXXXXX
     */
    public static function generateSalonCode(): string
    {
        $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        do {
            $randomStr = '';
            for ($i = 0; $i < 6; $i++) {
                $randomStr .= $chars[random_int(0, strlen($chars) - 1)];
            }
            $code = 'SL-' . $randomStr;
        } while (self::where('salon_code', $code)->exists());

        return $code;
    }
}
