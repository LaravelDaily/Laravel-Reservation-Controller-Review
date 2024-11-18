<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreReservationRequest extends FormRequest
{
    public function authorize()
    {
        return auth()->user()->tokenCan('reservations.make');
    }

    public function rules()
    {
        return [
            'office_id' => ['required', 'integer', 'exists:offices,id'],
            'start_date' => ['required', 'date', 'after:today'],
            'end_date' => ['required', 'date', 'after:start_date'],
        ];
    }

    public function messages()
    {
        return [
            'office_id.required' => 'Office is required',
            // Add other custom messages as needed
        ];
    }
}