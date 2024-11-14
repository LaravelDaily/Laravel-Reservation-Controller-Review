<?php

use App\Services\OfficeService;

beforeEach(function () {
    $this->officeService = new OfficeService();
});

it('calculates one-day price correctly', function () {
    $price = $this->officeService->getReservationPrice(100, 0, '2024-11-14', '2024-11-15');
    expect($price)->toEqual(100);
});

it('calculates monthly discount price correctly', function () {
    $price = $this->officeService->getReservationPrice(100, 20, '2024-11-14', '2024-12-14');

    // 30 days, price should be 3000, but with 20% discount it's 2400
    expect($price)->toEqual(2400);
});
