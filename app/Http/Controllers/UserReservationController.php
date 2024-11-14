<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreReservationRequest;
use App\Http\Resources\ReservationResource;
use App\Models\Office;
use App\Models\Reservation;
use App\Notifications\NewHostReservation;
use App\Notifications\NewUserReservation;
use App\Services\OfficeService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class UserReservationController extends Controller
{
    public function index()
    {

    }

    public function create()
    {
    }

    public function store(StoreReservationRequest $request, OfficeService $officeService): ReservationResource
    {
        $data = $request->validated();
        $office = $officeService->getOfficeDataForReservation(
            $data['office_id'],
            auth()->id(),
            $data['start_date'],
            $data['end_date']
        );

        $price = $officeService->getReservationPrice(
            $office->price_per_day,
            $office->monthly_discount,
            $data['start_date'],
            $data['end_date']
        );

        $reservation = Cache::lock('reservations_office_' . $office->id, 10)->block(3, function () use ($data, $office, $price) {
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

        Notification::send(auth()->user(), new NewUserReservation($reservation));
        Notification::send($office->user, new NewHostReservation($reservation));

        return new ReservationResource($reservation->load('office'));
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
