<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreReservationRequest;
use App\Http\Resources\ReservationResource;
use App\Services\ReservationService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class UserReservationController extends Controller
{
    // Claude did not suggest this. Manual addition
    use AuthorizesRequests;

    public function __construct(
        private readonly ReservationService $reservationService
    )
    {
    }

    public function store(StoreReservationRequest $request): ReservationResource
    {
        $this->authorize('make-reservation');

        $reservation = $this->reservationService->createReservation(
            $request->validated(),
            auth()->id()
        );

        return new ReservationResource($reservation->load('office'));
    }
}