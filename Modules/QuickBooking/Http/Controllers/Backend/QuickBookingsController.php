<?php

namespace Modules\QuickBooking\Http\Controllers\Backend;

use App\Events\Backend\UserCreated;
use App\Http\Controllers\Controller;
use App\Models\Address;
// Traits
use App\Models\Branch;
// Listing Models
use App\Models\User;
use App\Notifications\UserAccountCreated;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Modules\Booking\Models\Booking;
// Events
use Modules\Booking\Trait\BookingTrait;
use Modules\Service\Transformers\ServiceResource;
use Modules\Tax\Models\Tax;

class QuickBookingsController extends Controller
{
    use BookingTrait;

    public function index()
    {
        if (! setting('is_quick_booking')) {
            return abort(404);
        }

        return view('quickbooking::backend.quickbookings.index');
    }

    // API Methods for listing api
    public function branch_list()
    {
        $list = Branch::active()->with('address')->select('id', 'name', 'branch_for', 'contact_number', 'contact_email')->get();

        return $this->sendResponse($list, __('booking.booking_branch'));
    }

    public function slot_time_list(Request $request)
    {
        $day = date('l', strtotime($request->date));

        $data = $this->requestData($request);

        $slots = $this->getSlots($data['date'], $day, $data['branch_id'], $data['employee_id']);

        return $this->sendResponse($slots, __('booking.booking_timeslot'));
    }

    public function services_list(Request $request)
    {
        $branch_id = $request->branch_id;

        $data = $this->requestData($request);

        $item = Branch::find($data['branch_id']);

        $items = $item->services->where('status', 1);

        $list = ServiceResource::collection($items);

        return $this->sendResponse($list, __('booking.booking_sevice'));
    }

    public function employee_list(Request $request)
    {
        $data = $this->requestData($request);

        $list = User::whereHas('services', function ($query) use ($data) {
            $query->where('service_id', $data['service_id']);
        })
            ->whereHas('branches', function ($query) use ($data) {
                $query->where('branch_id', $data['branch_id']);
            })
            ->get();

        return $this->sendResponse($list, __('booking.booking_employee'));
    }

    // Create Method for Booking API
    public function create_booking(Request $request)
    {
        $userRequest = $request->user;
        $user = null;
        if (!empty($userRequest['email'])) {
            $user = User::where('email', $userRequest['email'])->first();
        }
        if (!$user && !empty($userRequest['mobile'])) {
            $user = User::where('mobile', $userRequest['mobile'])->first();
        }

        if (! isset($user)) {
            $rawPassword = !empty($userRequest['password']) ? $userRequest['password'] : '12345678';
            $userRequest['password'] = Hash::make($rawPassword);
            $userRequest['email_verified_at'] = null;

            $user = User::create($userRequest);
            $roles = ['user'];
            $user->syncRoles($roles);

            if (!empty($userRequest['profile_image']) && str_contains($userRequest['profile_image'], 'base64')) {
                try {
                    storeMediaFile($user, $userRequest['profile_image'], 'profile_image');
                } catch (\Exception $e) {
                    \Log::error('Erreur enregistrement photo profil client: '.$e->getMessage());
                }
            }

            \Artisan::call('cache:clear');

            event(new UserCreated($user));

            $data = [
                'password' => $rawPassword,
            ];

            try {
                if ($user->email) {
                    $user->notify(new UserAccountCreated($data));
                }
            } catch (\Exception $e) {
                \Log::error($e->getMessage());
            }
        }

        $bookingData = $request->booking;
        $bookingData['user_id'] = $user->id;
        $bookingData['created_by'] = $user->id;
        $bookingData['updated_by'] = $user->id;
        $booking = Booking::create($bookingData);

        $this->updateBookingService($bookingData['services'], $booking->id);

        $booking['user'] = $booking->user;

        $booking['services'] = $booking->services;

        $booking['branch'] = $booking->branch;

        $branchAddress = Address::where('addressable_id', $booking['branch']->id)
            ->where('addressable_type', get_class($booking['branch']))
            ->first();

        $booking['branch_address'] = $branchAddress;

        try {
            $this->sendNotificationOnBookingUpdate('quick_booking', $booking);
            // Notification au manager du salon concerné
            if ($booking->branch && $booking->branch->manager_id) {
                $manager = User::find($booking->branch->manager_id);
                if ($manager) {
                    $manager->notify(new \App\Notifications\CommonNotification([
                        'subject' => 'Nouvelle Réservation Client !',
                        'message' => 'Nouveau rendez-vous enregistré par '.$user->full_name.' au salon '.$booking->branch->name,
                        'type' => 'booking_created'
                    ]));
                }
            }
        } catch (\Exception $e) {
            \Log::error($e->getMessage());
        }

              $booking['tax'] = Tax::active()
              ->whereNull('module_type')
              ->orWhere('module_type', 'services')
              ->get()
              ->map(function ($tax) {
             return [
                 'name' => $tax->title,
                 'type' => $tax->type,
                 'percent' => $tax->type == 'percent' ? $tax->value : 0,
                 'tax_amount' => $tax->type != 'percent' ? $tax->value : 0,
             ];
         })
         ->toArray();

        return $this->sendResponse($booking, __('booking.booking_create'));
    }

    public function requestData($request)
    {
        return [
            'branch_id' => $request->branch_id,
            'service_id' => $request->service_id,
            'date' => $request->date,
            'employee_id' => $request->employee_id,
            'start_date_time' => $request->start_date_time,
        ];
    }

    public function check_review_eligibility(Request $request)
    {
        $identifier = trim($request->input('identifier', ''));
        if (empty($identifier)) {
            return response()->json([
                'status' => false,
                'message' => 'Veuillez saisir votre numéro de téléphone ou votre adresse e-mail.'
            ]);
        }

        $rawInput = trim($request->input('identifier', ''));
        $digitsInput = preg_replace('/\D/', '', $rawInput);

        $user = User::where(function ($q) use ($rawInput, $digitsInput) {
            if (!empty($rawInput)) {
                $q->where('email', $rawInput)
                  ->orWhere('mobile', $rawInput)
                  ->orWhere('mobile', 'like', '%' . $rawInput . '%');
            }
            if (!empty($digitsInput) && strlen($digitsInput) >= 6) {
                $lastDigits = substr($digitsInput, -8);
                $q->orWhereRaw("REPLACE(REPLACE(REPLACE(mobile, ' ', ''), '+', ''), '-', '') LIKE ?", ['%' . $lastDigits . '%']);
            }
        })->first();

        if (!$user) {
            return response()->json([
                'status' => false,
                'can_review' => false,
                'message' => 'Impossible d\'évaluer : Aucun compte client n\'a été trouvé avec ces coordonnées. Veuillez d\'abord effectuer une réservation !'
            ]);
        }

        $completedBooking = Booking::with('employee')->where('user_id', $user->id)
            ->where(function ($q) {
                $q->where('status', 'completed')
                  ->orWhere('start_date_time', '<', now());
            })
            ->orderBy('start_date_time', 'desc')
            ->first();

        if (!$completedBooking) {
            return response()->json([
                'status' => false,
                'can_review' => false,
                'message' => 'Impossible d\'évaluer : Vous n\'avez pas encore été soumis à nos services ou votre rendez-vous n\'est pas encore terminé. Merci de réserver un rendez-vous !'
            ]);
        }

        $assignedEmployee = $completedBooking->employee ?? User::role('employee')->first();

        return response()->json([
            'status' => true,
            'can_review' => true,
            'user' => [
                'id' => $user->id,
                'full_name' => $user->full_name,
                'initials' => strtoupper(substr($user->first_name ?? 'K', 0, 1) . substr($user->last_name ?? 'S', 0, 1)),
                'is_verified' => $user->email_verified_at !== null
            ],
            'booking_employee' => [
                'id' => $assignedEmployee ? $assignedEmployee->id : 1,
                'name' => $assignedEmployee ? $assignedEmployee->full_name . ' (' . ($assignedEmployee->email ?? 'Staff Salon') . ')' : 'Coiffeur Salon'
            ],
            'message' => 'Vous êtes éligible pour évaluer nos prestations !'
        ]);
    }
}
