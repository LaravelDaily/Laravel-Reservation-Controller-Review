<?php

namespace App\Http\Controllers;

use App\Actions\CreateReservationAction;
use App\Http\Resources\ReservationResource;
use App\Models\Office;
use Illuminate\Http\Request;
use App\Jobs\SendReservationNotificationsJob;
use App\Http\Requests\StoreReservationRequest;

class UserReservationController extends Controller
{
    public function index()
    {

    }

    public function create()
    {
    }

    public function store(StoreReservationRequest $request): ReservationResource
    {
        $office = Office::findOrFail($request->integer('office_id'));

        $reservation = CreateReservationAction::execute($request->validated(), $office);

        SendReservationNotificationsJob::dispatch($office->user, $reservation);

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
