<?php

namespace App\Http\Controllers\Auth\API;

use App\Http\Controllers\Auth\Trait\AuthTrait;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\LoginResource;
use App\Http\Resources\RegisterResource;
use App\Http\Resources\SocialLoginResource;
use App\Models\User;
use App\Notifications\AccountAutoEmailCreated;
use Auth;
use Hash;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use App\Models\Branch;
use Modules\Employee\Models\BranchEmployee;

class AuthController extends Controller
{
    use AuthTrait;

    public function register(Request $request)
    {
        $request->validate([
            'first_name'  => 'required|string|max:191',
            'last_name'   => 'required|string|max:191',
            'salon_name'  => 'required|string|max:191',
            'email'       => 'nullable|email|max:191',
            'mobile'      => 'required|string|max:191',
            'password'    => 'required|string|min:8',
        ]);

        $mobile = trim($request->mobile);
        $email  = $request->email ? strtolower(trim($request->email)) : null;

        // A client, un personnel ou un manager occupe déjà ce numéro.
        if (User::where('mobile', $mobile)->exists()) {
            return response()->json([
                'status' => false,
                'message' => 'Ce numéro de téléphone est déjà utilisé pour un compte.',
                'error_code' => 'MOBILE_ALREADY_USED',
            ], 422);
        }

        // Vérifier l'unicité de l'email s'il est fourni
        if ($email && User::where('email', $email)->exists()) {
            return response()->json([
                'status'  => false,
                'message' => 'Cette adresse email est déjà utilisée par un autre compte.',
            ], 422);
        }

        // Générer un email automatique si aucun email fourni (inscription depuis l'app mobile)
        $emailWasGenerated = false;
        if (!$email) {
            $email = $this->generateUniqueEmail(
                trim($request->first_name),
                trim($request->last_name),
                $mobile
            );
            $emailWasGenerated = true;
        }

        $plainPassword = $request->password;

        // Création d'un nouveau compte manager
        $user = DB::transaction(function () use ($request, $mobile, $email) {
            $user = User::create([
                'first_name'              => trim($request->first_name),
                'last_name'               => trim($request->last_name),
                'email'                   => $email,
                'mobile'                  => $mobile,
                'password'                => Hash::make($request->password),
                'email_verified_at'       => now(),
                'status'                  => 1,
                'is_manager'              => 1,
                'show_in_calender'        => 1,
                'mobile_trial_started_at' => now(),
            ]);
            $user->syncRoles(['employee', 'manager']);

            $branch = Branch::create([
                'name'           => trim($request->salon_name),
                'manager_id'     => $user->id,
                'contact_email'  => $email,
                'contact_number' => $mobile,
                'status'         => 1,
                'branch_for'     => 'both',
            ]);

            $user->update(['branch_id' => $branch->id]);
            BranchEmployee::firstOrCreate([
                'employee_id' => $user->id,
                'branch_id'   => $branch->id,
            ], ['is_primary' => 1]);

            return $user->fresh();
        });

        // Envoyer notification in-app si l'email a été généré automatiquement
        if ($emailWasGenerated) {
            $user->notify(new AccountAutoEmailCreated($email, $plainPassword, 'manager'));
        }

        $user['api_token'] = $user->createToken(setting('app_name'))->plainTextToken;

        return $this->sendResponse(
            new LoginResource($user),
            'Compte créé. Votre essai gratuit de 3 jours est maintenant actif.'
        );
    }

    /**
     * Login api
     *
     * @return \Illuminate\Http\Response
     */
    public function login(LoginRequest $request)
    {
        // Supporter login par email OU numéro de téléphone (champ 'email' ou 'contact_number' selon le client)
        $rawField = request('email') ?: request('contact_number') ?: '';
        $loginField = strtolower(trim($rawField));
        $password = request('password');

        // Essayer d'abord par email
        $user = User::where('email', $loginField)->first();

        // Si pas trouvé par email, essayer par numéro de téléphone
        if ($user == null) {
            $user = User::where('mobile', $loginField)->first();
        }

        if ($user == null) {
            return response()->json([
                'status' => false,
                'message' => 'Aucun compte ne correspond à ce numéro de téléphone ou à cette adresse e-mail.',
                'error_code' => 'ACCOUNT_NOT_FOUND',
            ], 401);
        }

        // Déterminer le champ d'authentification (email ou mobile)
        $authField = filter_var($loginField, FILTER_VALIDATE_EMAIL) ? 'email' : 'mobile';

        if (Auth::attempt([$authField => $loginField, 'password' => $password])) {
            $user = Auth::user();

            if ($user->is_banned == 1 || $user->status == 0) {
                return response()->json(['status' => false, 'message' => __('messages.login_error')]);
            }

            $user->player_id = $request->input('player_id'); // Store the player_id

            // Save the user
            $user->save();

            if ($user->mobile_trial_started_at === null) {
                $user->mobile_trial_started_at = now();
                $user->save();
            }
            $user['api_token'] = $user->createToken(setting('app_name'))->plainTextToken;

            $loginResource = new LoginResource($user);
            $message = __('messages.user_login');

            return $this->sendResponse($loginResource, $message);
        } else {
            return response()->json([
                'status' => false,
                'message' => 'Le mot de passe est incorrect pour ce compte.',
                'error_code' => 'INVALID_PASSWORD',
            ], 401);
        }
    }

    public function socialLogin(Request $request)
    {
        return $this->sendError(
            'Les comptes mobiles sont créés et activés uniquement par un administrateur.',
            403
        );

        /*$input = $request->all();

        if ($input['login_type'] === 'mobile') {
            $user_data = User::where('username', $input['username'])->where('login_type', 'mobile')->first();
        } else {
            $user_data = User::where('email', $input['email'])->first();
        }

        if ($user_data != null) {
            if (! isset($user_data->login_type) || $user_data->login_type == '') {
                if ($request->login_type === 'google') {
                    $message = __('validation.unique', ['attribute' => 'email']);
                } else {
                    $message = __('validation.unique', ['attribute' => 'username']);
                }

                return $this->sendError($message, 400);
            }
            $message = __('messages.login_success');
        } else {
            if ($request->login_type === 'google') {
                $key = 'email';
                $value = $request->email;
            } else {
                $key = 'username';
                $value = $request->username;
            }

            $trashed_user_data = User::where($key, $value)->whereNotNull('login_type')->withTrashed()->first();

            if ($trashed_user_data != null && $trashed_user_data->trashed()) {
                if ($request->login_type === 'google') {
                    $message = __('validation.unique', ['attribute' => 'email']);
                } else {
                    $message = __('validation.unique', ['attribute' => 'username']);
                }

                return $this->sendError($message, 400);
            }

            if ($request->login_type === 'mobile' && $user_data == null) {
                $otp_response = [
                    'status' => true,
                    'is_user_exist' => false,
                ];

                return $this->sendError($otp_response);
            }

            if ($request->login_type === 'mobile' && $user_data != null) {
                $otp_response = [
                    'status' => true,
                    'is_user_exist' => true,
                ];

                return $this->sendError($otp_response);
            }

            $password = ! empty($input['accessToken']) ? $input['accessToken'] : $input['email'];

            $input['user_type'] = 'user';
            $input['display_name'] = $input['first_name'].' '.$input['last_name'];
            $input['password'] = Hash::make($password);
            $input['user_type'] = isset($input['user_type']) ? $input['user_type'] : 'user';
            if (request('player_id') != null) {
                $input['player_id'] = request('player_id');
            }
            $user = User::create($input);
            $user->assignRole('user');

            \Artisan::call('cache:clear');

            if (! empty($input['profile_image'])) {
                $media = $user->addMediaFromUrl($input['profile_image'])->toMediaCollection('profile_image');
                $user->avatar = $media->getUrl();
            }
            $user_data = User::where('id', $user->id)->first();
            $message = trans('messages.save_form', ['form' => $input['user_type']]);
        }

        if (request('player_id') != null) {
            $user_data->player_id = request('player_id');
            $user_data->save();
        }
        $user_data['api_token'] = $user_data->createToken('auth_token')->plainTextToken;

        $socialLogin = new SocialLoginResource($user_data);

        return $this->sendResponse($socialLogin, $message);*/
    }

    public function logout(Request $request)
    {
        $user = Auth::guard('sanctum')->user();

        if ($request->is('api*')) {
            $user->player_id = null;
            $user->save();

            return response()->json(['status' => true, 'message' => __('messages.user_logout')]);
        }
    }

    public function forgotPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        // We will send the password reset link to this user. Once we have attempted
        // to send the link, we will examine the response then see the message we
        // need to show to the user. Finally, we'll send out a proper response.
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

    public function changePassword(Request $request)
    {
        $user = \Auth::user();
        $user_id = ! empty($request->id) ? $request->id : $user->id;
        $user = User::where('id', $user_id)->first();
        if ($user == '') {
            return response()->json([
                'status' => false,
                'message' => __('messages.user_notfound'),
            ], 400);
        }

        $hashedPassword = $user->password;

        $match = Hash::check($request->old_password, $hashedPassword);

        $same_exits = Hash::check($request->new_password, $hashedPassword);

        if ($match) {
            if ($same_exits) {
                $message = __('messages.old_new_pass_same');

                return response()->json([
                    'status' => false,
                    'message' => __('messages.same_pass'),
                ], 400);
            }

            $user->fill([
                'password' => Hash::make($request->new_password),
            ])->save();

            $success['api_token'] = $user->createToken(setting('app_name'))->plainTextToken;
            $success['name'] = $user->name;

            return response()->json([
                'status' => true,
                'data' => $success,
                'message' => __('messages.pass_successfull'),
            ], 200);
        } else {
            $success['api_token'] = $user->createToken(setting('app_name'))->plainTextToken;
            $success['name'] = $user->name;
            $message = __('messages.valid_password');

            return response()->json([
                'status' => true,
                'data' => $success,
                'message' => __('messages.pass_successfull'),
            ], 200);
        }
    }

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
            'status' => true,
            'data' => $user_data,
            'message' => $message,
        ], 200);
    }

    public function userDetails(Request $request)
    {
        $userID = $request->id ?: \Auth::id();
        $user = User::find($userID);
        if (! $user) {
            return response()->json(['status' => false, 'message' => __('messages.user_notfound')], 404);
        }

        return response()->json(['status' => true, 'data' => new LoginResource($user), 'message' => __('messages.user_details_successfull')]);
    }

    public function deleteAccount(Request $request)
    {
        $user_id = \Auth::user()->id;
        $user = User::where('id', $user_id)->first();
        if ($user == null) {
            $message = __('messages.user_not_found');

            return response()->json([
                'status' => false,
                'message' => $message,
            ], 200);
        }
        $user->booking()->forceDelete();
        $user->forceDelete();
        $message = __('messages.delete_account');

        return response()->json([
            'status' => true,
            'message' => $message,
        ], 200);
    }

    /**
     * Génère un email unique automatiquement au format prenom.nom.mobile@salon.app
     * Ajoute un suffixe numérique si l'email existe déjà.
     */
    protected function generateUniqueEmail(string $firstName, string $lastName, string $mobile): string
    {
        // Normaliser : minuscules, supprimer accents et caractères spéciaux
        $first  = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $this->removeAccents($firstName)));
        $last   = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $this->removeAccents($lastName)));
        $phone  = preg_replace('/[^0-9]/', '', $mobile);

        $base  = "{$first}.{$last}.{$phone}@salon.app";
        $email = $base;
        $i     = 1;

        while (User::where('email', $email)->exists()) {
            $email = "{$first}.{$last}.{$phone}{$i}@salon.app";
            $i++;
        }

        return $email;
    }

    /**
     * Supprime les accents d'une chaîne (é→e, ç→c, etc.)
     */
    protected function removeAccents(string $str): string
    {
        $from = ['à','â','ä','á','ã','å','è','é','ê','ë','ì','î','ï','ó','ô','ö','ò','õ','ù','û','ü','ú','ý','ÿ','ñ','ç',
                 'À','Â','Ä','Á','Ã','Å','È','É','Ê','Ë','Ì','Î','Ï','Ó','Ô','Ö','Ò','Õ','Ù','Û','Ü','Ú','Ý','Ñ','Ç'];
        $to   = ['a','a','a','a','a','a','e','e','e','e','i','i','i','o','o','o','o','o','u','u','u','u','y','y','n','c',
                 'A','A','A','A','A','A','E','E','E','E','I','I','I','O','O','O','O','O','U','U','U','U','Y','N','C'];
        return str_replace($from, $to, $str);
    }
}
