<?php

namespace App\Rules;

use Closure;
use App\Models\Office;
use Illuminate\Contracts\Validation\ValidationRule;

class CanMakeReservationRule implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $office = Office::findOrFail($value);

        if ($office->user_id === auth()->id()) {
            $fail('You cannot make a reservation on your own office');
        }

        if ($office->hidden || $office->approval_status !== 'approved') {
            $fail('You cannot make a reservation on a hidden or unapproved office');
        }
    }
}
