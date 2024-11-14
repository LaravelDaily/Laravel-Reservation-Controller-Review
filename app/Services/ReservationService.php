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
    public Office $office;

    public function setOffice(int $officeID): void
    {
        $this->office = Office::findOrFail($officeID);
    }

    public function checkIfOfficeIsAvailable(string $startDate, string $endDate): void
    {
        if ($this->office->user_id === auth()->id()) {
            throw ValidationException::withMessages(['office_id' => 'You cannot make a reservation on your own office']);
        }

        if ($this->office->hidden || $this->office->approval_status !== 'approved') {
            throw ValidationException::withMessages(['office_id' => 'You cannot make a reservation on a hidden or unapproved office']);
        }

        if ($this->office->reservations()->activeBetween($startDate, $endDate)->exists()) {
            throw ValidationException::withMessages(['office_id' => 'You cannot make a reservation during this time']);
        }
    }

    public function storeReservation(string $startDate, string $endDate): Reservation
    {
        return Cache::lock('reservations_office_'.$this->office->id, 10)
            ->block(3, function () use ($startDate, $endDate) {
                return Reservation::create([
                    'user_id' => auth()->id(),
                    'office_id' => $this->office->id,
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'status' => 'active',
                    'price' => $this->calculateReservationPrice($startDate, $endDate),
                    'wifi_password' => Str::random(),
                ]);
            });
    }

    public function calculateReservationPrice(string $startDate, string $endDate): float|int
    {
        $lengthInDays = $this->reservationLengthInDays($startDate, $endDate);

        $price = $lengthInDays * $this->office->price_per_day;

        if ($lengthInDays >= 28 && $this->office->monthly_discount) {
            $price *= ((100 - $this->office->monthly_discount) / 100);
        }

        return $price;
    }

    public function reservationLengthInDays(string $startDate, string $endDate): float|int
    {
        return round(Carbon::parse($endDate)->endOfDay()->diffInDays(Carbon::parse($startDate)->startOfDay()) + 1) * -1;
    }
}
