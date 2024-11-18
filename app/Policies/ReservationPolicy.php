<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ReservationPolicy
{
    use HandlesAuthorization;

    public function create(User $user)
    {
        return $user->tokenCan('reservations.make');
    }
}
