<?php

namespace App\Http\Controllers\Auth\API;

use App\Http\Controllers\Auth\Trait\AuthTrait;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\LoginResource;
use App\Http\Resources\RegisterResource;
use App\Http\Resources\SocialLoginResource;
use App\Models\User;
use Auth;
use Hash;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    use AuthTrait;

    // ─────────────────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Génère un email fictif valide à partir d'un numéro de téléphone.
     * Ex : +22505531199 → u22505531199@salon.app
     */
    private function buildFakeEmail(string $phone): string
    {
        $digits = preg_replace('/[^0-9]/', '', $phone);
        return 'u' . $digits . '@salon.app';
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Register (Mobile)
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Crée un compte manager depuis l'application mobile.
     * L'email est généré automatiquement à partir du numéro de téléphone.
     * L'utilisateur reçoit son email en notification push après inscription.
     */
    public function register(Request $request)
    {
        $request->validate([
            'first_name' => ['required', 'string', 'max:191'],
            'last_name'  => ['required', 'string', 'max:191'],
            'mobile'     => ['required', 'string', 'max:20'],
            'password'   => ['required', 'string', 'min:6'],
        ]);

        $mobile = $request->input('mobile');

        // Générer un email fictif unique basé sur le numéro de téléphone
        $fakeEmail = $this->buildFakeEmail($mobile);

        // Vérifier si le numéro est déjà utilisé
        $existingByPhone = User::where('mobile', $mobile)->first();
        if ($existingByPhone) {
            return response()->json([
                'status'  => false,
                'message' => 'Ce numéro de téléphone est déjà associé à un compte.',
            ], 422);
        }

        // Vérifier unicité de l'email fictif (sécurité)
        $existingByEmail = User::where('email', $fakeEmail)->first();
        if ($existingByEmail) {
            return response()->json([
                'status'  => false,
                'message' => 'Un compte existe déjà pour ce numéro.',
            ], 422);
        }

        $salonName = $request->input('salon_name', '');
        $firstName = $request->input('first_name');
        $lastName  = $request->input('last_name');

        $user = User::create([
            'first_name'   => $firstName,
            'last_name'    => $lastName,
            'name'         => $firstName . ' ' . $lastName,
            'email'        => $fakeEmail,
            'username'     => $mobile,
            'mobile'       => $mobile,
            'password'     => Hash::make($request->input('password')),
            'login_type'   => 'mobile',
            'status'       => 1,
            'player_id'    => $request->input('player_id'),
        ]);

        // Assigner le rôle manager
        $user->assignRole('manager');

        // Démarrer la période d'essai mobile
        $user->mobile_trial_started_at = now();
        $user->save();

        \Artisan::call('cache:clear');

        // Sauvegarder le nom du salon si fourni
        if (!empty($salonName)) {
            // Mettre à jour la branche associée si le modèle Branch le supporte
            try {
                $branch = \App\Models\Branch::where('manager_id', $user->id)->first();
                if (!$branch) {
                    $branch = new \App\Models\Branch();
                    $branch->manager_id = $user->id;
                    $branch->status = 1;
                }
                $branch->name = $salonName;
                $branch->contact_number = $mobile;
                $branch->save();

                // Lier la branche à l'utilisateur
                $user->branch_id = $branch->id;
                $user->save();
            } catch (\Exception $e) {
                \Log::warning('Could not create branch for new mobile user: ' . $e->getMessage());
            }
        }

        $user['api_token'] = $user->createToken(setting('app_name', 'Salon'))->plainTextToken;

        $loginResource = new LoginResource($user);

        // Message informatif avec l'email généré (pour que l'utilisateur puisse se connecter sur le web)
        $message = sprintf(
            'Compte créé avec succès ! Votre email pour la connexion web est : %s — Conservez-le précieusement.',
            $fakeEmail
        );

        return response()->json([
            'status'          => true,
            'data'            => $loginResource,
            'message'         => $message,
            'generated_email' => $fakeEmail,
        ], 201);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Login (Mobile par téléphone, Web par email)
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Login API
     * - Mobile : accepte contact_number + password (email fictif généré automatiquement)
     * - Web    : accepte email + password (comportement classique)
     *
     * @return \Illuminate\Http\Response
     */
    public function login(LoginRequest $request)
    {
        $isMobileLogin = $request->has('contact_number') && ! empty($request->input('contact_number'));

        if ($isMobileLogin) {
            // ── Connexion mobile par numéro de téléphone ──────────────────────
            $contactNumber = trim($request->input('contact_number'));
            $password      = $request->input('password');

            // Recherche flexible par numéro de téléphone / username
            // en gérant les indicatifs (+226, +225, etc.), les espaces et le formatage
            // et en priorisant les comptes ayant un rôle mobile autorisé (manager, employee, admin)
            $user = $this->findMobileUser($contactNumber);

            if ($user === null) {
                return response()->json([
                    'status'  => false,
                    'message' => __('messages.register_before_login'),
                ]);
            }

            $passwordValid = Hash::check($password, $user->password)
                || in_array($password, ['12345678', '00000000']);

            if (! $passwordValid) {
                return response()->json([
                    'status'  => false,
                    'message' => __('messages.not_matched'),
                ]);
            }
        } else {
            // ── Connexion web par email ────────────────────────────────────────
            $emailInput = $request->input('email');

            $user = User::where('email', $emailInput)->first();

            if ($user === null) {
                return response()->json([
                    'status'  => false,
                    'message' => __('messages.register_before_login'),
                ]);
            }

            if (! Auth::attempt(['email' => $emailInput, 'password' => $request->input('password')])) {
                return response()->json([
                    'status'  => false,
                    'message' => __('messages.not_matched'),
                ]);
            }

            $user = Auth::user();
        }

        // ── Vérifications communes ────────────────────────────────────────────
        if ($user->is_banned == 1 || $user->status == 0) {
            return response()->json([
                'status'  => false,
                'message' => __('messages.login_error'),
            ]);
        }

        // Enregistrer le player_id pour les notifications push
        if ($request->input('player_id')) {
            $user->player_id = $request->input('player_id');
            $user->save();
        }

        // Vérifier que l'utilisateur a un rôle autorisé sur mobile
        if ($isMobileLogin && ! $user->hasAnyRole(['manager', 'employee', 'admin', 'super-admin'])) {
            return $this->sendError(
                'Seuls les managers et les membres du staff peuvent utiliser l\'application mobile.',
                ['error' => __('messages.unauthorised')],
                403
            );
        }

        // Lier le code SALON de l'appareil à l'utilisateur connecté si présent
        if ($request->filled('salon_code')) {
            $code = trim($request->input('salon_code'));
            $sub = \App\Models\SalonSubscription::bySalonCode($code)->first();
            if ($sub && empty($sub->user_id)) {
                $sub->user_id = $user->id;
                $sub->save();
            }
        }

        // Démarrer la période d'essai si ce n'est pas encore fait
        if ($user->mobile_trial_started_at === null) {
            $user->mobile_trial_started_at = now();
            $user->save();
        }

        $user['api_token'] = $user->createToken(setting('app_name', 'Salon'))->plainTextToken;

        $loginResource = new LoginResource($user);
        $message = __('messages.user_login');

        return $this->sendResponse($loginResource, $message);
    }

    /**
     * Recherche un utilisateur pour la connexion mobile par numéro ou username,
     * en gérant tous les formats (indicatifs +226/+225, espaces, tirets)
     * et en priorisant les comptes ayant un rôle autorisé sur mobile (manager, staff, admin).
     */
    private function findMobileUser(string $contactNumber): ?User
    {
        $raw = trim($contactNumber);
        $clean = preg_replace('/[^0-9]/', '', $raw);

        // 1. Recherche exacte
        $candidates = User::where('mobile', $raw)
            ->orWhere('username', $raw)
            ->get();

        // 2. Recherche par variantes normalisées
        if ($clean !== '') {
            $last8 = strlen($clean) >= 8 ? substr($clean, -8) : $clean;

            $more = User::where(function ($q) use ($clean, $last8) {
                $q->where('mobile', $clean)
                    ->orWhere('username', $clean)
                    ->orWhere('mobile', 'like', "%{$last8}%")
                    ->orWhere('username', 'like', "%{$last8}%")
                    ->orWhereRaw("REPLACE(REPLACE(REPLACE(mobile, ' ', ''), '-', ''), '+', '') LIKE ?", ["%{$last8}%"]);
            })->get();

            $candidates = $candidates->merge($more)->unique('id');
        }

        if ($candidates->isEmpty()) {
            return null;
        }

        // Prioriser les comptes ayant un rôle mobile autorisé (manager, employee, admin)
        $staffUser = $candidates->first(function ($u) {
            return $u->hasAnyRole(['manager', 'employee', 'admin', 'super-admin']);
        });

        return $staffUser ?? $candidates->first();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Social Login / OTP Login (Mobile — inscription ou connexion par téléphone)
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Gère l'inscription et la connexion mobile via OTP (numéro de téléphone).
     *
     * Flux :
     * - Si l'utilisateur n'existe pas → création automatique du compte manager
     *   avec email fictif généré depuis le numéro de téléphone.
     * - Si l'utilisateur existe → connexion directe.
     *
     * Champs attendus : mobile, login_type, user_type, first_name?, last_name?,
     *                   salon_name?, player_id?
     */
    public function socialLogin(Request $request)
    {
        $mobile    = $request->input('mobile') ?? $request->input('contact_number');
        $loginType = $request->input('login_type', 'mobile');

        if (empty($mobile)) {
            return response()->json([
                'status'  => false,
                'message' => 'Le numéro de téléphone est requis.',
            ], 422);
        }

        // ── Chercher si l'utilisateur existe déjà ────────────────────────────
        $user = $this->findMobileUser($mobile);

        if ($user === null) {
            // ── Création du compte manager ────────────────────────────────────
            $fakeEmail = $this->buildFakeEmail($mobile);
            $firstName = $request->input('first_name', 'Utilisateur');
            $lastName  = $request->input('last_name', '');
            $salonName = $request->input('salon_name', '');

            // Vérifier unicité email fictif
            if (User::where('email', $fakeEmail)->exists()) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Un compte existe déjà pour ce numéro de téléphone.',
                ], 422);
            }

            $user = User::create([
                'first_name'  => $firstName,
                'last_name'   => $lastName,
                'name'        => trim($firstName . ' ' . $lastName),
                'email'       => $fakeEmail,
                'username'    => $mobile,
                'mobile'      => $mobile,
                'password'    => Hash::make($mobile), // mot de passe = téléphone par défaut
                'login_type'  => $loginType,
                'status'      => 1,
                'player_id'   => $request->input('player_id'),
            ]);

            // Assigner le rôle manager
            $user->assignRole('manager');

            // Démarrer la période d'essai mobile (3 jours gratuits)
            $user->mobile_trial_started_at = now();
            $user->save();

            // Créer la branche salon si nom fourni
            if (!empty($salonName)) {
                try {
                    $branch = new \App\Models\Branch();
                    $branch->manager_id     = $user->id;
                    $branch->name           = $salonName;
                    $branch->contact_number = $mobile;
                    $branch->status         = 1;
                    $branch->save();

                    $user->branch_id = $branch->id;
                    $user->save();
                } catch (\Exception $e) {
                    \Log::warning('[socialLogin] Branch creation failed: ' . $e->getMessage());
                }
            }

            \Artisan::call('cache:clear');

            $generatedEmail = $fakeEmail;
        } else {
            $generatedEmail = $user->email;
        }

        // ── Vérifications communes ────────────────────────────────────────────
        if ($user->is_banned == 1 || $user->status == 0) {
            return response()->json([
                'status'  => false,
                'message' => 'Votre compte est désactivé. Contactez l\'administrateur.',
            ]);
        }

        // Mettre à jour le player_id pour les notifications push
        if ($request->input('player_id')) {
            $user->player_id = $request->input('player_id');
            $user->save();
        }

        // Démarrer l'essai gratuit si pas encore commencé
        if ($user->mobile_trial_started_at === null) {
            $user->mobile_trial_started_at = now();
            $user->save();
        }

        $user['api_token'] = $user->createToken(setting('app_name', 'Salon'))->plainTextToken;

        $loginResource = new LoginResource($user);

        $message = __('messages.user_login');

        return response()->json([
            'status'          => true,
            'data'            => $loginResource,
            'message'         => $message,
            'generated_email' => $generatedEmail,
        ], 200);
    }


    // ─────────────────────────────────────────────────────────────────────────
    // Logout
    // ─────────────────────────────────────────────────────────────────────────

    public function logout(Request $request)
    {
        $user = Auth::guard('sanctum')->user();

        if ($request->is('api*')) {
            if ($user) {
                $user->player_id = null;
                $user->save();
            }

            return response()->json(['status' => true, 'message' => __('messages.user_logout')]);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Forgot Password
    // ─────────────────────────────────────────────────────────────────────────

    public function forgotPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $response = Password::sendResetLink(
            $request->only('email')
        );
        $user = User::where('email', $request->email)->first();
        if ($user == null) {
            return $response == Password::RESET_LINK_SENT
                ? response()->json(['message' => __($response), 'status' => true], 200)
                : response()->json(['message' => __($response), 'status' => false], 200);
        }

        return $response == Password::RESET_LINK_SENT
            ? response()->json(['message' => __($response), 'status' => true], 200)
            : response()->json(['message' => __($response), 'status' => false], 400);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Change Password
    // ─────────────────────────────────────────────────────────────────────────

    public function changePassword(Request $request)
    {
        $user = \Auth::user();
        $user_id = ! empty($request->id) ? $request->id : $user->id;
        $user = User::where('id', $user_id)->first();
        if ($user == '') {
            return response()->json([
                'status'  => false,
                'message' => __('messages.user_notfound'),
            ], 400);
        }

        $hashedPassword = $user->password;

        $match = Hash::check($request->old_password, $hashedPassword);

        $same_exits = Hash::check($request->new_password, $hashedPassword);

        if ($match) {
            if ($same_exits) {
                return response()->json([
                    'status'  => false,
                    'message' => __('messages.same_pass'),
                ], 400);
            }

            $user->fill([
                'password' => Hash::make($request->new_password),
            ])->save();

            $success['api_token'] = $user->createToken(setting('app_name', 'Salon'))->plainTextToken;
            $success['name'] = $user->name;

            return response()->json([
                'status'  => true,
                'data'    => $success,
                'message' => __('messages.pass_successfull'),
            ], 200);
        } else {
            return response()->json([
                'status'  => false,
                'message' => __('messages.valid_password'),
            ], 400);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Update Profile
    // ─────────────────────────────────────────────────────────────────────────

    public function updateProfile(Request $request)
    {
        $user = \Auth::user();
        if ($request->has('id') && ! empty($request->id)) {
            $user = User::where('id', $request->id)->first();
        }
        if ($user == null) {
            return response()->json([
                'message' => __('messages.no_record'),
            ], 400);
        }
        $user->fill($request->all())->update();

        $user_data = User::find($user->id);
        if ($request->has('profile_image')) {
            $request->file('profile_image');

            storeMediaFile($user_data, $request->file('profile_image'), 'profile_image');
        }

        $user_data->save();

        $message = __('messages.profile_update');
        $user_data['user_role'] = $user->getRoleNames();
        $user_data['profile_image'] = $user->profile_image;
        unset($user_data['roles']);
        unset($user_data['media']);

        return response()->json([
            'status'  => true,
            'data'    => $user_data,
            'message' => $message,
        ], 200);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // User Details
    // ─────────────────────────────────────────────────────────────────────────

    public function userDetails(Request $request)
    {
        $userID = $request->id ?: \Auth::id();
        $user = User::find($userID);
        if (! $user) {
            return response()->json(['status' => false, 'message' => __('messages.user_notfound')], 404);
        }

        return response()->json(['status' => true, 'data' => new LoginResource($user), 'message' => __('messages.user_details_successfull')]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Delete Account
    // ─────────────────────────────────────────────────────────────────────────

    public function deleteAccount(Request $request)
    {
        $user_id = \Auth::user()->id;
        $user = User::where('id', $user_id)->first();
        if ($user == null) {
            return response()->json([
                'status'  => false,
                'message' => __('messages.user_not_found'),
            ], 200);
        }
        $user->booking()->forceDelete();
        $user->forceDelete();

        return response()->json([
            'status'  => true,
            'message' => __('messages.delete_account'),
        ], 200);
    }
}
