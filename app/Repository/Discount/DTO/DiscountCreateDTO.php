<?php

namespace App\Repository\Discount\DTO;

class DiscountCreateDTO
{
    public string $name;
    public ?string $code;
    public float $discount_value;
    public ?float $minimum_price;
    public ?array $profit_manager_ids;
    public bool $is_special;
    public ?int $customer_id;
    public ?int $usage_limit;
    public ?int $usage_count;
    public ?int $reserve_number;
    public bool $is_active;
    public string $discount_type;
    public string $is_unlimited;
    public string $scope;
    public ?string $starts_at;
    public ?string $expires_at;

    public function __construct(array $data)
    {
        $this->name = $data['name'];
        $this->code = $data['code'] ?? null;
        $this->discount_value = $data['discount_value'];
        $this->minimum_price = $data['minimum_price'] ?? null;
        $this->profit_manager_ids = $data['profit_manager_ids'] ?? null;
        $this->is_special = $data['is_special'] ?? false;
        $this->customer_id = $data['customer_id'] ?? null;
        $this->usage_limit = $data['usage_limit'] ?? null;
        $this->usage_count = $data['usage_count'] ?? 0;
        $this->reserve_number = $data['reserve_number'] ?? null;
        $this->is_active = $data['is_active'] ?? true;
        $this->discount_type = $data['discount_type'];
        $this->scope = $data['scope'];
        $this->is_unlimited = $data['is_unlimited'] ?? false;
        $this->starts_at = $data['starts_at'] ?? null;
        $this->expires_at = $data['expires_at'] ?? null;
    }

    public function toArray(): array
    {
        return get_object_vars($this);
    }

    public function setCode(string $code): self
    {
        $this->code = $code;
        return $this;
    }
}