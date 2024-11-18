<?php

namespace App\Exceptions;

use Exception;

class OwnOfficeReservationException extends Exception
{
    public function render()
    {
        return response()->json([
            'errors' => [
                'office_id' => 'You cannot make a reservation on your own office'
            ]
        ], 422);
    }
}
