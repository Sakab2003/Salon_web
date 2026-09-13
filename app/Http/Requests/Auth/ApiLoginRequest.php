<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

/**
 * FormRequest pour le login depuis l'application mobile (Flutter).
 * Accepte uniquement contact_number + password.
 * N'exige jamais de champ 'email'.
 */
class ApiLoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Validation : on accepte contact_number OU email (pour compatibilité),
     * mais le mobile n'envoie que contact_number.
     */
    public function rules(): array
    {
        return [
            'contact_number' => ['required_without:email', 'nullable', 'string'],
            'email'          => ['required_without:contact_number', 'nullable', 'string'],
            'password'       => ['required', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'contact_number.required_without' => 'Le numéro de téléphone est obligatoire.',
            'email.required_without'           => 'L\'email ou le numéro de téléphone est obligatoire.',
            'password.required'                => 'Le mot de passe est obligatoire.',
        ];
    }
}
