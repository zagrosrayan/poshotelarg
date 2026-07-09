<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Discount;
use App\Models\Food;
use App\Models\Order;
use App\Models\Type;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class DiscountExpirationTest extends TestCase
{
    use DatabaseTransactions;

    protected $user;
    protected $customer;
    protected $food;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->customer = Customer::factory()->create();
        $this->food = Food::factory()->create(['price' => 100000]);
        
        // Ensure Order Status exists
        if (!Type::where('slug', 'order-status-pending')->exists()) {
             Type::create(['slug' => 'order-status-pending', 'name' => 'Pending', 'category' => 'order_status']);
        }
    }

    public function test_create_order_with_expired_normal_discount_returns_specific_error()
    {
        $discount = Discount::create([
            'name' => 'Expired Normal Discount',
            'code' => 'EXP_NORMAL',
            'discount_value' => 10,
            'discount_type' => 'percentage',
            'is_active' => true,
            'scope' => 'normal',
            'starts_at' => now()->subDays(10),
            'expires_at' => now()->subDays(1),
        ]);

        $response = $this->actingAs($this->user)->postJson('/api/orders', [
            'service_type' => 'takeaway',
            'rate_service' => 0,
            'order' => [
                ['food_id' => $this->food->id, 'quantity' => 1]
            ],
            'discount_normal_code' => $discount->code,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['discount_normal_code']);

        $errors = $response->json('errors.discount_normal_code');
        $this->assertStringContainsString('|expired', $errors[0]);
    }

    public function test_update_order_with_expired_normal_discount_returns_specific_error()
    {
        $order = Order::create([
            'user_id' => $this->user->id,
            'status' => Type::where('slug', 'order-status-pending')->first()->id,
            'invoice_number' => rand(10000, 99999),
            'total_price' => 100000,
            'service_type' => 'takeaway',
        ]);

        $discount = Discount::create([
            'name' => 'Expired Normal Discount 2',
            'code' => 'EXP_NORMAL_2',
            'discount_value' => 10,
            'discount_type' => 'percentage',
            'is_active' => true,
            'scope' => 'normal',
            'starts_at' => now()->subDays(10),
            'expires_at' => now()->subDays(1),
        ]);

        $response = $this->actingAs($this->user)->putJson("/api/orders/{$order->id}", [
            'discount_normal_code' => $discount->code,
            'orders' => [
                ['quantity' => 1, 'description' => 'test'] // OrderController update requires 'orders' array or validates it? Let's check. 
                                                          // Looking at OrderController::update, it validates 'orders.*.quantity'.
                                                          // So we need to send orders array.
            ]
        ]);
        
        // Wait, OrderController::update logic might be different. 
        // It validates 'orders.*.quantity' IF 'orders' is present? 
        // Or assumes 'orders' is part of request?
        // Let's re-read OrderController::update validation rules.
        // It validates 'orders.*.quantity' => 'required|integer|min:1'.
        // This implies 'orders' array is expected if we want to pass validation, 
        // OR if 'orders' is optional, then 'orders.*' rules apply only if present.
        // However, usually update method updates the order items too.
        // Let's verify if 'orders' is required. It's not explicitly 'required' in the rules array I saw earlier.
        // But let's check line 1090: 'orders.*.quantity' => 'required...'.
        // If I don't send 'orders', does it fail? 
        // Let's assume we need to send at least one item to be safe, or check if existing items are used.
        // In the update method, it seems to iterate over existing children if no new orders provided? 
        // No, usually API expects the full state.
        // I'll send the orders array to be safe.
        
        // Also need to create a child order for the item
        Order::create([
            'parent_id' => $order->id,
            'food_id' => $this->food->id,
            'quantity' => 1,
            'price' => 100000,
            'total_price' => 100000,
             'status' => Type::where('slug', 'order-status-pending')->first()->id,
        ]);

         $response = $this->actingAs($this->user)->putJson("/api/orders/{$order->id}", [
            'discount_normal_code' => $discount->code,
            'orders' => [
                ['food_id' => $this->food->id, 'quantity' => 1] // Assuming food_id is needed for reconstruction or matching
            ],
            // Add other required fields if any. 
            // Looking at validation: 'service_type' is checked for desc_number.
             'service_type' => 'takeaway', 
        ]);


        $response->assertStatus(422)
            ->assertJsonValidationErrors(['discount_normal_code']);

        $errors = $response->json('errors.discount_normal_code');
        $this->assertStringContainsString('|expired', $errors[0]);
    }

    public function test_create_order_with_expired_next_purchase_discount_returns_specific_error()
    {
        $discount = Discount::create([
            'name' => 'Expired Next Purchase',
            'code' => 'EXP_NEXT', // Next purchase usually doesn't use code for lookup in request, but customer_id
            'discount_value' => 10,
            'discount_type' => 'percentage',
            'is_active' => true,
            'scope' => 'next_purchase',
            'customer_id' => $this->customer->id,
            'starts_at' => now()->subDays(10),
            'expires_at' => now()->subDays(1),
            'usage_limit' => 1,
            'usage_count' => 0,
        ]);

        $response = $this->actingAs($this->user)->postJson('/api/orders', [
            'service_type' => 'takeaway',
            'rate_service' => 0,
            'order' => [
                ['food_id' => $this->food->id, 'quantity' => 1]
            ],
            'customer_id' => $this->customer->id,
            'use_next_purchase_discount' => true,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['use_next_purchase_discount']);

        $errors = $response->json('errors.use_next_purchase_discount');
        $this->assertStringContainsString('|expired', $errors[0]);
    }
    
    public function test_update_order_with_expired_next_purchase_discount_returns_specific_error()
    {
         $order = Order::create([
            'user_id' => $this->user->id,
            'customer_id' => $this->customer->id,
            'status' => Type::where('slug', 'order-status-pending')->first()->id,
            'invoice_number' => rand(10000, 99999),
            'total_price' => 100000,
            'service_type' => 'takeaway',
        ]);
        
        Order::create([
            'parent_id' => $order->id,
            'food_id' => $this->food->id,
            'quantity' => 1,
            'price' => 100000,
            'total_price' => 100000,
             'status' => Type::where('slug', 'order-status-pending')->first()->id,
        ]);

        $discount = Discount::create([
            'name' => 'Expired Next Purchase 2',
            'code' => 'EXP_NEXT_2',
            'discount_value' => 10,
            'discount_type' => 'percentage',
            'is_active' => true,
            'scope' => 'next_purchase',
            'customer_id' => $this->customer->id,
            'starts_at' => now()->subDays(10),
            'expires_at' => now()->subDays(1),
            'usage_limit' => 1,
            'usage_count' => 0,
        ]);

        $response = $this->actingAs($this->user)->putJson("/api/orders/{$order->id}", [
            'customer_id' => $this->customer->id,
            'use_next_purchase_discount' => true,
             'orders' => [
                ['food_id' => $this->food->id, 'quantity' => 1]
            ],
             'service_type' => 'takeaway',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['use_next_purchase_discount']);

        $errors = $response->json('errors.use_next_purchase_discount');
        $this->assertStringContainsString('|expired', $errors[0]);
    }
}
