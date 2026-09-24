<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\Branch;
use App\Models\User;

class LoginResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $roles = $this->getRoleNames()->values()->all();
        $branch = null;
        if ($this->hasRole('manager')) {
            $branch = $this->customerBranch ?: Branch::where('manager_id', $this->id)->first();
        } elseif ($this->hasRole('employee')) {
            $branch = $this->mainBranch()->first() ?: $this->branch()->with('getBranch')->first()?->getBranch;
        } elseif ($this->branch_id) {
            $branch = Branch::find($this->branch_id);
        }

        if (!$branch) {
            $branch = Branch::where('status', 1)->first();
        }

        $branchId = $branch?->id ?: 1;
        $publicBookingUrl = url('/reservation-rapide?salon_id=' . $branchId);
        $isManager = $this->hasRole('manager') || $this->is_manager == 1 || $this->hasRole('admin') || $this->hasRole('super-admin');

        $salonSub = \App\Models\SalonSubscription::where('user_id', $this->id)->first();
        if (!$salonSub && request()->filled('salon_code')) {
            $salonSub = \App\Models\SalonSubscription::bySalonCode(request()->input('salon_code'))->first();
            if ($salonSub && empty($salonSub->user_id)) {
                $salonSub->user_id = $this->id;
                $salonSub->save();
            }
        }

        $subscriptionData = null;
        if ($salonSub && $salonSub->isActive()) {
            $planType = ucfirst($salonSub->subscription_type ?? 'Mensuel');
            $subscriptionData = [
                'status'         => 'active',
                'name'           => "Abonnement {$planType}",
                'plan_type'      => $planType,
                'days_remaining' => $salonSub->getRemainingDays(),
                'start_date'     => $salonSub->subscription_start_date,
                'end_date'       => $salonSub->subscription_end_date,
                'salon_code'     => $salonSub->salon_code,
            ];
        } elseif ($this->subscriptionPackage) {
            $subscriptionData = [
                'status'         => $this->subscriptionPackage->status,
                'name'           => $this->subscriptionPackage->name,
                'plan_type'      => stripos($this->subscriptionPackage->name, 'annuel') !== false ? 'Annuel' : 'Mensuel',
                'days_remaining' => (int) max(1, ceil(now()->diffInDays($this->subscriptionPackage->end_date, false))),
                'start_date'     => $this->subscriptionPackage->start_date,
                'end_date'       => $this->subscriptionPackage->end_date,
            ];
        }

        return [
            'id' => $this->id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'mobile' => $this->mobile,
            'email' => $this->email,
            'gender' => $this->gender,
            'user_role' => $roles,
            'branch_id' => $branch?->id,
            'branch' => $branch ? [
                'id' => $branch->id,
                'name' => $branch->name,
                'contact_number' => $branch->contact_number,
                'public_booking_url' => $publicBookingUrl,
            ] : null,
            'permissions' => $this->getAllPermissions()->pluck('name')->values()->all(),
            'subscription' => $subscriptionData,
            'user_type' => $roles[0] ?? ($isManager ? 'manager' : ($this->hasRole('employee') ? 'employee' : 'user')),
            'is_manager' => $isManager,
            'can_manage_users' => $isManager,
            'show_user_management' => $isManager,
            'public_booking_url' => $publicBookingUrl,
            'share_links' => [
                'direct' => $publicBookingUrl,
                'whatsapp' => 'https://api.whatsapp.com/send?text=' . urlencode('Bonjour ! Prenez rendez-vous directement au salon ' . ($branch?->name ?: '') . ' en suivant ce lien : ' . $publicBookingUrl),
                'facebook' => 'https://www.facebook.com/sharer/sharer.php?u=' . urlencode($publicBookingUrl),
                'twitter' => 'https://twitter.com/intent/tweet?text=' . urlencode('Bonjour ! Prenez rendez-vous directement au salon ' . ($branch?->name ?: '') . ' : ' . $publicBookingUrl),
                'tiktok' => $publicBookingUrl,
                'instagram' => $publicBookingUrl,
            ],
            'display_name' => trim($this->first_name . ' ' . $this->last_name),
            'mobile_trial_started_at' => $this->mobile_trial_started_at,
            'mobile_trial_days' => User::MOBILE_TRIAL_DAYS,
            'mobile_trial_days_remaining' => $this->mobileTrialDaysRemaining(),
            'mobile_access' => $this->hasMobileAccess(),
            'api_token' => $this->api_token,
            'login_type' => $this->login_type,
            'profile_image' => $this->media->pluck('original_url')->first() ?: $this->avatar,
        ];
    }
}
