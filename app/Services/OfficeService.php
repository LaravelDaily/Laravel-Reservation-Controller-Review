<?php

namespace App\Services;

use App\Models\Office;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

class OfficeService
{
    public function getOfficeDataForReservation(int $officeId, int $userId, string $startDate, string $endDate): Office|ValidationException
    {
        $office = Office::findOrFail($officeId);

        if ($office->user_id === $userId) {
            throw ValidationException::withMessages(['office_id' => 'You cannot make a reservation on your own office']);
        }

        if ($office->hidden || $office->approval_status !== 'approved') {
            throw ValidationException::withMessages(['office_id' => 'You cannot make a reservation on a hidden or unapproved office']);
        }

        if ($office->reservations()->activeBetween($startDate, $endDate)->exists()) {
            throw ValidationException::withMessages(['office_id' => 'You cannot make a reservation during this time']);
        }

        return $office;
    }

    public function getReservationPrice(int|float $pricePerDay, int|float $monthlyDiscount, string $startDate, string $endDate): int|float
    {
        $numberOfDays = round(Carbon::parse($endDate)->endOfDay()->diffInDays(Carbon::parse($startDate)->startOfDay()) + 1) * -1;

        $price = $numberOfDays * $pricePerDay;

        if ($numberOfDays >= 28 && $monthlyDiscount) {
            $price = $price * ((100 - $monthlyDiscount) / 100);
        }

        return $price;
    }
}
