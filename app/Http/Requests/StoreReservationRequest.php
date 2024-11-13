<?php

namespace App\Http\Requests;

use App\Rules\CanMakeReservationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreReservationRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'office_id'  => ['required', 'integer', 'exists:offices,id', new CanMakeReservationRule],
            'start_date' => ['required', 'date', 'after:today'],
            'end_date'   => ['required', 'date', 'after:start_date'],
        ];
    }

    public function authorize(): bool
    {
        return $this->user()->tokenCan('reservations.make');
    }
}
