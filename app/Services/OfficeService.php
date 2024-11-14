<?php

namespace App\Services;

use App\Models\Office;
use Illuminate\Validation\ValidationException;

class OfficeService
{
    public function getOfficeDataForReservation(int $officeId, int $userId): Office|ValidationException
    {
        $office = Office::findOrFail($officeId);

        if ($office->user_id === $userId) {
            throw ValidationException::withMessages(['office_id' => 'You cannot make a reservation on your own office']);
        }

        if ($office->hidden || $office->approval_status !== 'approved') {
            throw ValidationException::withMessages(['office_id' => 'You cannot make a reservation on a hidden or unapproved office']);
        }

        return $office;
    }
}
