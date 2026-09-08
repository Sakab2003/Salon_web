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
        $rules = [
            'name' => 'required|string|max:255',
            'service_id' => 'required|exists:services,id',
            'commission_id' => 'nullable|integer|exists:commissions,id',
            'status' => 'required|in:0,1',
            'remove_image_ids' => 'nullable|array',
            'remove_image_ids.*' => 'integer',
        ];

        if ($this->isMethod('post')) {
            $rules['feature_image'] = 'required';
            $rules['feature_image.*'] = 'image|mimes:jpeg,png,jpg,gif,svg,webp|max:10240';
        } else {
            $rules['feature_image'] = 'nullable';
            $rules['feature_image.*'] = 'image|mimes:jpeg,png,jpg,gif,svg,webp|max:10240';
        }

        $rules['branch_id'] = 'nullable|integer|exists:branches,id';

        return $rules;
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
