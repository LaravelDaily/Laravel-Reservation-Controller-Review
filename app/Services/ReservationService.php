<?php

namespace App\Services;

use App\Models\Office;
use App\Models\Reservation;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

class ReservationService
{
    public function createReservation(array $data, int $userId): Reservation
    {
        $office = Office::findOrFail($data['office_id']);

        $this->validateOffice($office, $userId);

        return Cache::lock('reservations_office_' . $office->id, 10)->block(3, function () use ($data, $office, $userId) {
            $this->checkAvailability($office, $data['start_date'], $data['end_date']);

            $price = $this->calculatePrice($office, $data['start_date'], $data['end_date']);

            return Reservation::create([
                'user_id' => $userId,
                'office_id' => $office->id,
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],
                'status' => 'active',
                'price' => $price,
                'wifi_password' => Str::random(),
            ]);
        });
    }

    protected function validateOffice(Office $office, int $userId): void
    {
        if ($office->user_id === $userId) {
            throw ValidationException::withMessages(['office_id' => 'You cannot make a reservation on your own office.']);
        }

        if ($office->hidden || $office->approval_status !== 'approved') {
            throw ValidationException::withMessages(['office_id' => 'You cannot make a reservation on a hidden or unapproved office.']);
        }
    }

    protected function checkAvailability(Office $office, string $startDate, string $endDate): void
    {
        $activeReservations = $office->reservations()
            ->activeBetween($startDate, $endDate)
            ->exists();

        if ($activeReservations) {
            throw ValidationException::withMessages(['office_id' => 'You cannot make a reservation during this time.']);
        }
    }

    protected function calculatePrice(Office $office, string $startDate, string $endDate): float
    {
        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->endOfDay();
        $numberOfDays = $end->diffInDays($start) + 1;

        $price = $numberOfDays * $office->price_per_day;

        if ($numberOfDays >= 28 && $office->monthly_discount) {
            $price *= (100 - $office->monthly_discount) / 100;
        }

        return $price;
    }
}
