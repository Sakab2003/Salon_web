<?php

namespace Modules\Service\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class HairstyleModelRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'service_id' => 'required|exists:services,id',
            'status' => 'required|in:0,1',
            'feature_image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:4096',
        ];
    }

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }
}
