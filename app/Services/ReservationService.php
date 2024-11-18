<?php

namespace App\Services;

use App\Models\Office;
use App\Models\Reservation;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ReservationService
{
    public function createReservation($user, Office $office, array $data): Reservation
    {
        $lock = Cache::lock('reservations_office_' . $office->id, 10);

        if ($lock->get()) {
            try {
                $numberOfDays = Carbon::parse($data['start_date'])->diffInDays(Carbon::parse($data['end_date']));

                if ($office->reservations()->activeBetween($data['start_date'], $data['end_date'])->exists()) {
                    throw ValidationException::withMessages(['office_id' => 'You cannot make a reservation during this time']);
                }

                $price = $this->calculatePrice($office, $numberOfDays);

                return Reservation::create([
                    'user_id' => $user->id,
                    'office_id' => $office->id,
                    'start_date' => $data['start_date'],
                    'end_date' => $data['end_date'],
                    'status' => 'active',
                    'price' => $price,
                    'wifi_password' => Str::random(),
                ]);
            } finally {
                $lock->release();
            }
        } else {
            throw ValidationException::withMessages(['office_id' => 'Unable to acquire lock. Please try again later.']);
        }
    }

    private function calculatePrice(Office $office, int $numberOfDays): float
    {
        $price = $numberOfDays * $office->price_per_day;

        if ($numberOfDays >= 28 && $office->monthly_discount) {
            $price *= (100 - $office->monthly_discount) / 100;
        }

        return $price;
    }
}