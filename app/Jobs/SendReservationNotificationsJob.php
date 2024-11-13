<?php

namespace App\Jobs;

use App\Models\User;
use App\Models\Reservation;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use App\Notifications\NewUserReservation;
use App\Notifications\NewHostReservation;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Notification;

class SendReservationNotificationsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(protected readonly User $user, protected readonly Reservation $reservation)
    {}

    public function handle(): void
    {
        Notification::send(auth()->user(), new NewUserReservation($this->reservation));
        Notification::send($this->user, new NewHostReservation($this->reservation));
    }
}
