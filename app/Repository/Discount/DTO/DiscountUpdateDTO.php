<?php

namespace App\Repository\Discount\DTO;

class DiscountUpdateDTO
{
    public string $name;
    public float $discount_value;
    public ?float $minimum_price;
    public ?int $profit_manager_id;
    public bool $is_special;
    public ?int $customer_id;
    public ?int $reserve_number;
    public bool $is_active;
    public string $discount_type;
    public ?string $starts_at;
    public ?string $expires_at;
    public ?int $usage_limit;

    public function __construct(array $data)
    {
        $this->name = $data['name'];
        $this->discount_value = $data['discount_value'];
        $this->minimum_price = $data['minimum_price'] ?? null;
        $this->profit_manager_id = $data['profit_manager_id'] ?? null;
        $this->is_special = $data['is_special'] ?? false;
        $this->customer_id = $data['customer_id'] ?? null;
        $this->reserve_number = $data['reserve_number'] ?? null;
        $this->is_active = $data['is_active'] ?? true;
        $this->discount_type = $data['discount_type'];
        $this->starts_at = $data['starts_at'] ?? null;
        $this->expires_at = $data['expires_at'] ?? null;
        $this->usage_limit = $data['usage_limit'] ?? null;
    }

    public function toArray(): array
    {
        return get_object_vars($this);
    }
}