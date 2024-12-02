<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreReservationRequest;
use App\Http\Resources\ReservationResource;
use App\Models\Reservation;
use App\Notifications\NewHostReservation;
use App\Notifications\NewUserReservation;
use App\Services\ReservationService;
use Illuminate\Support\Facades\Notification;

class UserReservationController extends Controller
{
    protected $reservationService;

    public function __construct(ReservationService $reservationService)
    {
        $this->reservationService = $reservationService;
    }

    public function store(StoreReservationRequest $request): ReservationResource
    {
        $user = $request->user();
        $data = $request->validated();

        $reservation = $this->reservationService->createReservation($data, $user->id);

        // Load related office data
        $reservation->load('office');

        // Send notifications
        Notification::send($user, new NewUserReservation($reservation));
        Notification::send($reservation->office->user, new NewHostReservation($reservation));

        return new ReservationResource($reservation);
    }

    // ... other methods (index, create, show, edit, update, destroy)
}
