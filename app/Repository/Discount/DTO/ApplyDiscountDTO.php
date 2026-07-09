<?php

namespace App\Repository\Discount\DTO;

class ApplyDiscountDTO
{
    public string $code;
    public float $total_price;
    public ?int $user_id;
    public ?int $profit_manager_id;
    public ?int $customer_id = null;
    public ?int $reserve_number = null;
    public ?int $product_id;

    public function __construct(array $data)
    {
        $this->code = $data['code'];
        $this->reserve_number = $data['reserve_number'];
        $this->customer_id = $data['customer_id'];
        $this->profit_manager_id = $data['profit_manager_id'];
        $this->total_price = $data['total_price'];
        $this->user_id = $data['user_id'] ?? null;
        $this->product_id = $data['product_id'] ?? null;
    }
}
