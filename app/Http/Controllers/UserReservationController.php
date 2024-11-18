<?php

namespace App\Http\Controllers;


use App\Http\Resources\ReservationResource;
use App\Models\Office;
use App\Models\Reservation;
use App\Notifications\NewHostReservation;
use App\Notifications\NewUserReservation;
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

    public function store(Request $request): ReservationResource
    {
        $this->authorizeReservation();

        $data = $this->validateReservation($request);

        $office = $this->validateOffice($data['office_id']);

        $reservation = $this->createReservation($data, $office);

        $this->notifyUsers($reservation, $office);

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

    protected function authorizeReservation()
    {
        abort_unless(auth()->user()->tokenCan('reservations.make'), 403);
    }

    protected function validateReservation(Request $request): array
    {
        return $request->validate([
            'office_id' => ['required', 'integer', 'exists:offices,id'],
            'start_date' => ['required', 'date', 'after:today'],
            'end_date' => ['required', 'date', 'after:start_date'],
        ]);
    }

    protected function validateOffice(int $officeId): Office
    {
        $office = Office::findOrFail($officeId);

        if ($office->user_id === auth()->id()) {
            throw ValidationException::withMessages(['office_id' => 'You cannot make a reservation on your own office']);
        }

        if ($office->hidden || $office->approval_status !== 'approved') {
            throw ValidationException::withMessages(['office_id' => 'You cannot make a reservation on a hidden or unapproved office']);
        }

        return $office;
    }

    protected function createReservation(array $data, Office $office): Reservation
    {
        return Cache::lock('reservations_office_' . $office->id, 10)->block(3, function () use ($data, $office) {
            $this->checkReservationAvailability($office, $data['start_date'], $data['end_date']);
            $price = $this->calculateReservationPrice($office, $data['start_date'], $data['end_date']);

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

    protected function checkReservationAvailability(Office $office, string $startDate, string $endDate): void
    {
        if ($office->reservations()->activeBetween($startDate, $endDate)->exists()) {
            throw ValidationException::withMessages(['office_id' => 'You cannot make a reservation during this time']);
        }
    }

    protected function calculateReservationPrice(Office $office, string $startDate, string $endDate): float
    {
        $numberOfDays = (Carbon::parse($endDate)->endOfDay()->diffInDays(Carbon::parse($startDate)->startOfDay()) + 1) * -1;
        $price = $numberOfDays * $office->price_per_day;


        if ($numberOfDays >= 28 && $office->monthly_discount) {
            $price *= (100 - $office->monthly_discount) / 100;
        }

        return round($price, 2);
    }

    protected function notifyUsers(Reservation $reservation, Office $office): void
    {
        Notification::send(auth()->user(), new NewUserReservation($reservation));
        Notification::send($office->user, new NewHostReservation($reservation));
    }
}
