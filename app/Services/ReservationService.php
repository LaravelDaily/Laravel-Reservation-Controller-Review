<?php

namespace App\Services;

use App\Models\Office;
use App\Models\Reservation;
use App\Notifications\NewHostReservation;
use App\Notifications\NewUserReservation;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ReservationService
{
    public function createReservation(array $data, int $userId): Reservation
    {
        $office = Office::findOrFail($data['office_id']);

        $this->validateReservation($office, $userId);

        return Cache::lock('reservations_office_' . $office->id, 10)
            ->block(3, function () use ($data, $office, $userId) {
                $reservation = $this->processReservation($data, $office, $userId);
                $this->sendNotifications($reservation);

                return $reservation;
            });
    }

    private function validateReservation(Office $office, int $userId): void
    {
        if ($office->user_id === $userId) {
            throw ValidationException::withMessages([
                'office_id' => 'You cannot make a reservation on your own office'
            ]);
        }

        if ($office->hidden || $office->approval_status !== 'approved') {
            throw ValidationException::withMessages([
                'office_id' => 'You cannot make a reservation on a hidden or unapproved office'
            ]);
        }
    }

    private function processReservation(array $data, Office $office, int $userId): Reservation
    {
        $numberOfDays = $this->calculateNumberOfDays($data['start_date'], $data['end_date']);

        if ($office->reservations()->activeBetween($data['start_date'], $data['end_date'])->exists()) {
            throw ValidationException::withMessages([
                'office_id' => 'You cannot make a reservation during this time'
            ]);
        }

        $price = $this->calculatePrice($numberOfDays, $office);

        return Reservation::create([
            'user_id' => $userId,
            'office_id' => $office->id,
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'status' => 'active',
            'price' => $price,
            'wifi_password' => Str::random(),
        ]);
    }

    private function calculateNumberOfDays(string $startDate, string $endDate): int
    {
        return round(
                Carbon::parse($endDate)->endOfDay()
                    ->diffInDays(Carbon::parse($startDate)->startOfDay()) + 1
            ) * -1;
    }

    private function calculatePrice(int $numberOfDays, Office $office): float
    {
        $price = $numberOfDays * $office->price_per_day;

        if ($numberOfDays >= 28 && $office->monthly_discount) {
            $price = $price * ((100 - $office->monthly_discount) / 100);
        }

        return $price;
    }

    private function sendNotifications(Reservation $reservation): void
    {
        Notification::send(
            $reservation->user,
            new NewUserReservation($reservation)
        );

        Notification::send(
            $reservation->office->user,
            new NewHostReservation($reservation)
        );
    }
}