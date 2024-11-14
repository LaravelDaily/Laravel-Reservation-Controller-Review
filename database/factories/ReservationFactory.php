<?php

namespace Database\Factories;

use App\Models\Office;
use App\Models\Reservation;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;
use Str;

/**
 * @extends Factory<Reservation>
 */
class ReservationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startDate = Carbon::now()->addDays($this->faker->numberBetween(1, 30));
        $endDate = (clone $startDate)->addDays($this->faker->numberBetween(1, 14)); // End date 1-14 days after start date

        return [
            'user_id' => User::factory(), // Automatically create a user for each reservation
            'office_id' => Office::factory(), // Automatically create an office for each reservation
            'start_date' => $startDate->toDateString(),
            'end_date' => $endDate->toDateString(),
            'status' => 'active',
            'price' => fn (array $attributes) => Office::find($attributes['office_id'])->price_per_day * $startDate->diffInDays($endDate),
            'wifi_password' => Str::random(10),
        ];
    }
}
