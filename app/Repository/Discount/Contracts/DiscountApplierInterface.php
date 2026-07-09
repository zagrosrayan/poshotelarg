<?php

namespace App\Repository\Discount\Contracts;

use App\Repository\Discount\DTO\ApplyDiscountDTO;

interface DiscountApplierInterface
{
    public function apply(ApplyDiscountDTO $dto): array;
    public function calculate(ApplyDiscountDTO $dto): array;

    public function applyFinalize(string $discount_type, float $discount_value, float $totalPrice): array;
}