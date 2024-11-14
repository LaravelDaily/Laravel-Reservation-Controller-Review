<?php

namespace App\Observers;

use App\Models\Reservation;
use App\Notifications\NewHostReservation;
use App\Notifications\NewUserReservation;
use Illuminate\Support\Facades\Notification;

class ReservationObserver
{
    public function created(Reservation $reservation): void
    {
        $reservation->load(['office.user', 'user']);

        Notification::send($reservation->user, new NewUserReservation($reservation));
        Notification::send($reservation->office->user, new NewHostReservation($reservation));
    }
}
