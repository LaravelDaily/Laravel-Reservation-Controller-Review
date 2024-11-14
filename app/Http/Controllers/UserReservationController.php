<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreReservationRequest;
use App\Http\Resources\ReservationResource;
use App\Services\ReservationService;
use Illuminate\Http\Request;

class UserReservationController extends Controller
{
    public function index()
    {

    }

    public function create()
    {

    }

    public function store(StoreReservationRequest $request, ReservationService $service): ReservationResource
    {
        $data = $request->validated();

        $service->setOffice($data['office_id']);

        $service->checkIfOfficeIsAvailable(
            $data['start_date'],
            $data['end_date']
        );

        return new ReservationResource(
            $service->storeReservation($data['start_date'], $data['end_date'])
                ->load(['office'])
        );
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
