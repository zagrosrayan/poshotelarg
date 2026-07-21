<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Discount;
use App\Models\DiscountSmsDelivery;
use App\Models\Food;
use App\Models\NextPurchaseDiscount;
use App\Models\Order;
use App\Models\Setting;
use App\Models\Type;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class HotelArgFeaturesTest extends TestCase
{
    use DatabaseTransactions;

    protected $user;
    protected $customer;
    protected $food;
    protected $printerType;
    protected $orderStatusPending;
    protected $orderStatusComplete;

    protected function setUp(): void
    {
        parent::setUp();

        // Create necessary types if they don't exist
        $this->orderStatusPending = Type::firstOrCreate(['slug' => 'order-status-pending'], ['name' => 'Pending', 'category' => 'order_status']);
        $this->orderStatusComplete = Type::firstOrCreate(['slug' => 'order-status-complete'], ['name' => 'Complete', 'category' => 'order_status']);
        $this->printerType = Type::firstOrCreate(['slug' => 'laser-printer'], ['name' => 'Laser Printer', 'category' => 'printer_type']);
        Type::firstOrCreate(['slug' => 'payment-method-cash'], ['name' => 'Cash', 'category' => 'payment_method']);

        $this->user = User::factory()->create();
        $this->customer = Customer::factory()->create();
        $this->food = Food::factory()->create(['price' => 100000]);
    }

    /**
     * Test 1: Finalizing an order does not require order-complete SMS settings.
     */
    public function test_sms_sending_logic_on_create_and_finalize()
    {
        $response = $this->actingAs($this->user)->postJson('/api/orders', [
            'service_type' => 'takeaway',
            'rate_service' => 0,
            'order' => [
                ['food_id' => $this->food->id, 'quantity' => 1]
            ],
            'customer_id' => $this->customer->id,
        ]);

        $response->assertStatus(200);
        $orderId = $response->json('items.id');

        Setting::updateOrCreate(['id' => 1], [
            'tax' => 0,
            'rate_service' => 0
        ]);

        $finalizeResponse = $this->actingAs($this->user)->putJson("/api/orders/{$orderId}/complete", [
            'order_type' => 'guest',
            'payment_method' => Type::where('slug', 'payment-method-cash')->first()->id,
            'name' => $this->customer->name,
            'phone' => $this->customer->phone,
        ]);

        $finalizeResponse->assertStatus(200);
    }

    /**
     * Test 2: Next purchase discount is created and pattern SMS deliveries are scheduled.
     */
    public function test_next_purchase_discount_configuration_and_jobs()
    {
        $validityDays = 15;

        NextPurchaseDiscount::create([
            'name' => 'Test Next Purchase',
            'minimum_purchase_amount' => 50000,
            'discount_percentage' => 10,
            'is_active' => true,
            'discount_validity_days' => $validityDays,
            'target_customer_types' => ['Non_resident', 'resident'],
            'profit_manager_ids' => []
        ]);

        $order = Order::create([
            'user_id' => $this->user->id,
            'customer_id' => $this->customer->id,
            'status' => $this->orderStatusPending->id,
            'invoice_number' => rand(100000, 999999),
            'total_price' => 100000,
            'service_type' => 'takeaway',
        ]);

        Order::create([
            'parent_id' => $order->id,
            'food_id' => $this->food->id,
            'quantity' => 1,
            'price' => 100000,
            'total_price' => 100000,
            'status' => $this->orderStatusPending->id,
        ]);

        $finalizeResponse = $this->actingAs($this->user)->putJson("/api/orders/{$order->id}/complete", [
            'order_type' => 'guest',
            'payment_method' => Type::where('slug', 'payment-method-cash')->first()->id,
            'name' => $this->customer->name,
            'phone' => $this->customer->phone,
        ]);

        $finalizeResponse->assertStatus(200);

        $discount = Discount::where('scope', 'next_purchase')
            ->where('customer_id', $this->customer->id)
            ->latest()
            ->first();

        $this->assertNotNull($discount);
        $this->assertEquals(
            now()->addDays($validityDays)->format('Y-m-d'),
            $discount->expires_at->format('Y-m-d')
        );

        $issued = DiscountSmsDelivery::where('discount_id', $discount->id)
            ->where('type', DiscountSmsDelivery::TYPE_ISSUED)
            ->first();
        $reminder = DiscountSmsDelivery::where('discount_id', $discount->id)
            ->where('type', DiscountSmsDelivery::TYPE_REMINDER)
            ->first();

        $this->assertNotNull($issued);
        $this->assertSame(DiscountSmsDelivery::STATUS_PENDING, $issued->status);
        $this->assertSame(now()->toDateString(), $issued->scheduled_for->toDateString());
        $this->assertNotNull($reminder);
        $this->assertSame(
            $discount->expires_at->copy()->subDays(4)->toDateString(),
            $reminder->scheduled_for->toDateString()
        );
    }

    /**
     * Test 3: Invoice Details include discount_type.
     */
    public function test_invoice_details_include_discount_type()
    {
        $discount = Discount::create([
            'name' => 'Fixed Discount',
            'code' => 'FIXED100',
            'discount_value' => 5000,
            'discount_type' => 'fixed',
            'is_active' => true,
            'scope' => 'normal',
        ]);

        $order = Order::create([
            'user_id' => $this->user->id,
            'customer_id' => $this->customer->id,
            'status' => $this->orderStatusPending->id,
            'invoice_number' => rand(100000, 999999),
            'total_price' => 100000,
            'discount_id' => $discount->id,
            'discounted_price' => 95000,
            'service_type' => 'takeaway',
        ]);

        $response = $this->actingAs($this->user)->getJson("/api/orders/{$order->id}");

        $response->assertStatus(200)
            ->assertJsonPath('items.discount.discount_type', 'fixed');
    }

    /**
     * Test 4: Inactive Users Report.
     */
    public function test_inactive_users_report()
    {
        // Customer 1: Has recent order
        $activeCustomer = Customer::factory()->create(['name' => 'Active Customer']);
        Order::create([
            'user_id' => $this->user->id,
            'customer_id' => $activeCustomer->id,
            'status' => $this->orderStatusComplete->id,
            'created_at' => now(),
            'service_type' => 'takeaway',
        ]);

        // Customer 2: Has old order (Inactive)
        $inactiveCustomer = Customer::factory()->create(['name' => 'Inactive Customer']);
        Order::create([
            'user_id' => $this->user->id,
            'customer_id' => $inactiveCustomer->id,
            'status' => $this->orderStatusComplete->id,
            'created_at' => now()->subMonths(5),
            'service_type' => 'takeaway',
        ]);

        // Customer 3: No orders ever
        $neverOrderedCustomer = Customer::factory()->create(['name' => 'Never Ordered']);

        // Request report for users who haven't ordered in the last month
        $response = $this->actingAs($this->user)->getJson('/api/reports/customers?' . http_build_query([
            'no_order_from' => now()->subMonth()->format('Y-m-d'),
            'no_order_to' => now()->format('Y-m-d'),
        ]));

        $response->assertStatus(200);
        
        // Assert InactiveCustomer is in the list
        $ids = collect($response->json('items.data'))->pluck('id');
        $this->assertTrue($ids->contains($inactiveCustomer->id));
        $this->assertTrue($ids->contains($neverOrderedCustomer->id)); // Should also be included as they have NO orders in that range
        
        // Assert ActiveCustomer is NOT in the list
        $this->assertFalse($ids->contains($activeCustomer->id));
    }

    /**
     * Test 6: Profit Centers Checkbox (Multiple Selection).
     */
    public function test_discount_supports_multiple_profit_centers()
    {
        $discount = Discount::create([
            'name' => 'Multi Profit',
            'code' => 'MULTI',
            'discount_value' => 10,
            'is_active' => true,
            'profit_manager_ids' => [1, 2, 3], // Array
            'scope' => 'normal',
        ]);

        $this->assertIsArray($discount->profit_manager_ids);
        $this->assertCount(3, $discount->profit_manager_ids);
        $this->assertEquals([1, 2, 3], $discount->profit_manager_ids);
    }

    /**
     * Test 7: Next Purchase Discount - Profit Center & Resident Selection.
     */
    public function test_next_purchase_discount_target_types()
    {
        // 1. Discount for Residents Only
        NextPurchaseDiscount::create([
            'name' => 'Resident Only',
            'minimum_purchase_amount' => 1000,
            'discount_percentage' => 10,
            'is_active' => true,
            'target_customer_types' => ['resident'],
        ]);

        // Order for Guest (Non-Resident)
        $guestOrder = Order::create([
            'user_id' => $this->user->id,
            'customer_id' => $this->customer->id, // Has customer_id usually means guest/club member, not necessarily resident unless linked to room
            'status' => $this->orderStatusPending->id,
            'invoice_number' => rand(100000, 999999),
            'total_price' => 50000,
            'service_type' => 'takeaway',
        ]);
         Order::create([
            'parent_id' => $guestOrder->id,
            'food_id' => $this->food->id,
            'quantity' => 1,
            'price' => 50000,
            'total_price' => 50000,
            'status' => $this->orderStatusPending->id,
        ]);

        // Complete Order as Guest
        $this->actingAs($this->user)->putJson("/api/orders/{$guestOrder->id}/complete", [
            'order_type' => 'guest',
            'payment_method' => Type::where('slug', 'payment-method-cash')->first()->id,
            'name' => 'Guest',
            'phone' => '09120000000',
        ]);

        // Should NOT create discount because target is resident
        $discount = Discount::where('name', 'Resident Only')->first();
        $this->assertNull($discount);


        // 2. Order for Resident
        $residentOrder = Order::create([
            'user_id' => $this->user->id,
            'reserve_number' => 'RES123',
            'status' => $this->orderStatusPending->id,
            'invoice_number' => rand(100000, 999999),
            'total_price' => 50000,
            'service_type' => 'room_service',
        ]);
         Order::create([
            'parent_id' => $residentOrder->id,
            'food_id' => $this->food->id,
            'quantity' => 1,
            'price' => 50000,
            'total_price' => 50000,
            'status' => $this->orderStatusPending->id,
        ]);
        
        // Mock InhouseList check if needed, or assume order_type='resident' in request handles it
        // The controller uses $request->order_type to determine resident/guest logic.
        
        $this->actingAs($this->user)->putJson("/api/orders/{$residentOrder->id}/complete", [
            'order_type' => 'resident',
            'payment_method' => Type::where('slug', 'payment-method-cash')->first()->id,
            'reserve_number' => 'RES123',
        ]);

        // Should CREATE discount
        $discountCreated = Discount::where('name', 'Resident Only')->latest()->first();
        $this->assertNotNull($discountCreated);
    }

    /**
     * Test 10 & 12: Apply Discounts in Final Step & Expired Error.
     */
    public function test_apply_expired_discount_in_final_step_returns_specific_error()
    {
        $order = Order::create([
            'user_id' => $this->user->id,
            'status' => $this->orderStatusPending->id,
            'invoice_number' => rand(100000, 999999),
            'total_price' => 100000,
            'service_type' => 'takeaway',
        ]);
         Order::create([
            'parent_id' => $order->id,
            'food_id' => $this->food->id,
            'quantity' => 1,
            'price' => 100000,
            'total_price' => 100000,
            'status' => $this->orderStatusPending->id,
        ]);

        $expiredDiscount = Discount::create([
            'name' => 'Expired',
            'code' => 'EXP123',
            'discount_value' => 10,
            'is_active' => true,
            'scope' => 'normal',
            'expires_at' => now()->subDay(),
        ]);

        $response = $this->actingAs($this->user)->putJson("/api/orders/{$order->id}", [
            'discount_normal_code' => $expiredDiscount->code,
            'orders' => [
                ['food_id' => $this->food->id, 'quantity' => 1]
            ],
            'service_type' => 'takeaway',
        ]);

        $response->assertStatus(422);
        $errors = $response->json('errors.discount_normal_code');
        $this->assertStringContainsString('|expired', $errors[0]);
    }
}
