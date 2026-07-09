@foreach($foods as $food)
    <div class="food-section">
        <div class="food-header">
            <h2>{{ $food['food_name'] }}</h2>
            <span class="food-info">{{ $food['article'] ?? '-' }}</span>
            <span class="food-info">{{ $food['profit_manager'] ?? '-' }}</span>
        </div>

        <div class="summary-box">
        <span class="summary-inline">
            تعداد سفارشات: <strong>{{ number_format($food['summary']['order_count']) }}</strong>
        </span>
            <span class="summary-inline">
            تعداد کل: <strong>{{ number_format($food['summary']['total_quantity']) }}</strong>
        </span>
            <span class="summary-inline">
            قیمت میانگین: <strong>{{ number_format($food['summary']['average_price']) }}</strong>
        </span>
            <span class="summary-inline">
            مجموع فروش: <strong>{{ number_format($food['summary']['total_price']) }}</strong>
        </span>
        </div>

        @if(count($food['orders']) > 0)
            @php
                $totalQuantity = collect($food['orders'])->sum('quantity');
                $totalOrderPrice = collect($food['orders'])->sum('total_price');
                $totalTax = collect($food['orders'])->sum('tax');
                $totalService = collect($food['orders'])->sum('rate_service');
                $totalDiscount = collect($food['orders'])->sum('discounted_price');
                $totalFinal = collect($food['orders'])->sum('total');
            @endphp

            <table class="orders-table">
                <thead>
                <tr>
                    <th style="width: 4%">ردیف</th>
                    <th style="width: 8%">شماره فاکتور</th>
                    <th style="width: 6%">تعداد</th>
                    <th style="width: 10%">قیمت واحد</th>
                    <th style="width: 10%">قیمت کل</th>
                    <th style="width: 8%">مالیات</th>
                    <th style="width: 8%">سرویس</th>
                    <th style="width: 10%">تخفیف</th>
                    <th style="width: 12%">جمع نهایی</th>
                    <th style="width: 12%">تاریخ</th>
                </tr>
                </thead>
                <tbody>
                @foreach($food['orders'] as $orderIndex => $order)
                    <tr>
                        <td>{{ $orderIndex + 1 }}</td>
                        <td>{{ $order['invoice_number'] }}</td>
                        <td>{{ number_format($order['quantity']) }}</td>
                        <td class="price-col">{{ number_format($order['price']) }}</td>
                        <td class="price-col">{{ number_format($order['total_price']) }}</td>
                        <td class="price-col">{{ number_format($order['tax']) }}</td>
                        <td class="price-col">{{ number_format($order['rate_service']) }}</td>
                        <td class="price-col">{{ number_format($order['discounted_price']) }}</td>
                        <td class="price-col">{{ number_format($order['total']) }}</td>
                        <td style="font-size: 7px;">{{ $order['created_at'] }}</td>
                    </tr>
                @endforeach

                <tr class="total-row">
                    <td colspan="2">جمع کل {{ $food['food_name'] }}</td>
                    <td>{{ number_format($totalQuantity) }}</td>
                    <td class="price-col">-</td>
                    <td class="price-col">{{ number_format($totalOrderPrice) }}</td>
                    <td class="price-col">{{ number_format($totalTax) }}</td>
                    <td class="price-col">{{ number_format($totalService) }}</td>
                    <td class="price-col">{{ number_format($totalDiscount) }}</td>
                    <td class="price-col">{{ number_format($totalFinal) }}</td>
                    <td>-</td>
                </tr>
                </tbody>
            </table>
        @else
            <div class="no-orders">هیچ سفارشی یافت نشد</div>
        @endif
    </div>
@endforeach