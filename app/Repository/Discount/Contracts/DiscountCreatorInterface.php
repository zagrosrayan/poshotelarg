<?php

namespace App\Repository\Discount\Contracts;

use App\Models\Discount;
use App\Repository\Discount\DTO\DiscountCreateDTO;
use App\Repository\Discount\DTO\DiscountUpdateDTO;

interface DiscountCreatorInterface
{
    public function create(DiscountCreateDTO $dto): Discount;

    public function update(int $id, DiscountUpdateDTO $dto): Discount;
}