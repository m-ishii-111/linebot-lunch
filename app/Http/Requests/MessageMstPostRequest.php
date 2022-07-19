<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MessageMstPostRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'follow'          => 'required|string|max:255',
            'location'        => 'required|string|max:255',
            'location_button' => 'required|string|max:255',
            'stamp'           => 'required|string|max:255',
            'not_found'       => 'required|string|max:255',
        ];
    }

    public function messages()
    {
        return [
            'required' => '入力必須項目です。',
            'max' => ":max文字までです。",
        ];
    }
}
