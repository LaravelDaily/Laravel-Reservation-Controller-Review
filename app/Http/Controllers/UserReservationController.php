<?php

namespace App\Http\Controllers;

use App\Exceptions\OwnOfficeReservationException;
use App\Http\Requests\StoreReservationRequest;
use App\Http\Resources\ReservationResource;
use App\Models\Office;
use App\Models\Reservation;
use App\Notifications\NewHostReservation;
use App\Notifications\NewUserReservation;
use App\Services\ReservationService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

class UserReservationController extends Controller
{
    // CodeRabbit forgot this
    use AuthorizesRequests;

    public function index()
    {

    }

    public function create()
    {
    }

    public function store(StoreReservationRequest $request, ReservationService $reservationService): ReservationResource
    {
        $this->authorize('create', Reservation::class);

        $data = $request->validated();
        $user = auth()->user();

        $office = Office::findOrFail($data['office_id']);

        // Office-related validations
        $this->validateOffice($office);

        $reservation = $reservationService->createReservation($user, $office, $data);

        // Send Notifications
        dispatch(function () use ($user, $reservation) {
            Notification::send($user, new NewUserReservation($reservation));
        });

        dispatch(function () use ($office, $reservation) {
            Notification::send($office->user, new NewHostReservation($reservation));
        });

        return new ReservationResource($reservation->load('office'));
    }

    private function validateOffice(Office $office)
    {
        if ($office->user_id === auth()->id()) {
            throw new OwnOfficeReservationException();
        }

        if ($office->hidden || $office->approval_status !== 'approved') {
            throw ValidationException::withMessages(['office_id' => 'You cannot make a reservation on a hidden or unapproved office']);
        }
    }

    public function show($id)
    {
    }

    public function edit($id)
    {
    }

    public function update(Request $request, $id)
    {
    }

    public function destroy($id)
    {
    }
}
