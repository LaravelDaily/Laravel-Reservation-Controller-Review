<?php

use App\Models\Office;
use App\Models\Reservation;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

beforeEach(function () {
    Notification::fake();
    $this->user = User::factory()->create();
    $this->office = Office::factory()->create(['approval_status' => 'approved']);
});

it('allows a user with the correct token ability to make a reservation', function () {
    $this->office->hidden = false;
    $this->office->save();

    $token = $this->user->createToken('Test Token', ['reservations.make'])->plainTextToken;

    $startDate = Carbon::now()->addDays(1)->toDateString();
    $endDate = Carbon::now()->addDays(3)->toDateString();

    $response = $this->withToken($token)->postJson('/api/reservations', [
        'office_id' => $this->office->id,
        'start_date' => $startDate,
        'end_date' => $endDate,
    ]);

    $response->assertStatus(201);
    expect(Reservation::count())->toBe(1);
});

it('denies reservation creation if user does not have the correct token ability', function () {
    $token = $this->user->createToken('Test Token', ['other.ability'])->plainTextToken;

    $response = $this->withToken($token)->postJson('/api/reservations', [
        'office_id' => $this->office->id,
        'start_date' => now()->addDays(1)->toDateString(),
        'end_date' => now()->addDays(3)->toDateString(),
    ]);

    $response->assertStatus(403);
    expect(Reservation::count())->toBe(0);
});

it('prevents a user from making a reservation on their own office', function () {
    $this->office->user_id = $this->user->id;
    $this->office->save();

    $token = $this->user->createToken('Test Token', ['reservations.make'])->plainTextToken;

    $response = $this->withToken($token)->postJson('/api/reservations', [
        'office_id' => $this->office->id,
        'start_date' => now()->addDays(1)->toDateString(),
        'end_date' => now()->addDays(3)->toDateString(),
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['office_id' => 'You cannot make a reservation on your own office']);
});

it('prevents reservations for hidden or pending offices', function () {
    $this->office->hidden = true;
    $this->office->save();

    $token = $this->user->createToken('Test Token', ['reservations.make'])->plainTextToken;

    $response = $this->withToken($token)->postJson('/api/reservations', [
        'office_id' => $this->office->id,
        'start_date' => now()->addDays(1)->toDateString(),
        'end_date' => now()->addDays(3)->toDateString(),
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['office_id' => 'You cannot make a reservation on a hidden or unapproved office']);
});

it('prevents overlapping reservations', function () {
    $this->office->hidden = false;
    $this->office->save();
    Reservation::factory()->create([
        'office_id' => $this->office->id,
        'start_date' => now()->addDays(2)->toDateString(),
        'end_date' => now()->addDays(4)->toDateString(),
        'status' => 'active',
    ]);

    $token = $this->user->createToken('Test Token', ['reservations.make'])->plainTextToken;

    $response = $this->withToken($token)->postJson('/api/reservations', [
        'office_id' => $this->office->id,
        'start_date' => now()->addDays(3)->toDateString(),
        'end_date' => now()->addDays(5)->toDateString(),
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['office_id' => 'You cannot make a reservation during this time']);
});

it('applies a monthly discount for reservations longer than 28 days', function () {
    $this->office->price_per_day = 100;
    $this->office->monthly_discount = 10; // 10% discount
    $this->office->hidden = false;
    $this->office->save();

    $token = $this->user->createToken('Test Token', ['reservations.make'])->plainTextToken;

    $response = $this->withToken($token)->postJson('/api/reservations', [
        'office_id' => $this->office->id,
        'start_date' => now()->addDay()->toDateString(),
        'end_date' => now()->addDays(31)->toDateString(),
    ]);

    $response->assertStatus(201);

    $reservation = Reservation::first();
    $expectedPrice = (100 * 30) * 0.9; // 10% discount applied
    expect($reservation->price)->toEqual($expectedPrice);
});
