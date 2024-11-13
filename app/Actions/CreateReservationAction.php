<?php

namespace App\Actions;

use Carbon\Carbon;
use App\Models\Office;
use App\Models\Reservation;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;

class CreateReservationAction
{
    public static function execute(array $data, Office $office): Reservation
    {
        return Cache::lock('reservations_office_' . $office->id, 10)->block(3, function () use ($data, $office) {
            $numberOfDays = round(Carbon::parse($data['end_date'])->endOfDay()->diffInDays(Carbon::parse($data['start_date'])->startOfDay()) + 1) * -1;

            if ($office->reservations()->activeBetween($data['start_date'], $data['end_date'])->exists()) {
                throw ValidationException::withMessages(['office_id' => 'You cannot make a reservation during this time']);
            }

            $price = $numberOfDays * $office->price_per_day;

            if ($numberOfDays >= 28 && $office->monthly_discount) {
                $price = $price * ((100 - $office->monthly_discount) / 100);
            }

            return Reservation::create([
                'user_id' => auth()->id(),
                'office_id' => $office->id,
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],
                'status' => 'active',
                'price' => $price,
                'wifi_password' => Str::random(),
            ]);
        });
    }
}
