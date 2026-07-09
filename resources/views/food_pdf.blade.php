<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <style>
        @page {
            margin: 20px;
        }

        @font-face {
            font-family: 'Vazir';
            src: url('{{ public_path("fonts/Vazir.ttf") }}') format('truetype');
        }

        body {
            font-family: 'Vazir', sans-serif;
            direction: rtl;
            text-align: right;
            margin: 0;
            padding: 0;
            font-size: 10px;
            line-height: 1.4;
            color: #333;
        }

        .header {
            text-align: center;
            margin-bottom: 15px;
            border-bottom: 3px solid #2563eb;
            padding-bottom: 8px;
        }

        .header h1 {
            color: #17c964;
            font-size: 18px;
            font-weight: bold;
            margin: 0;
        }

        .food-section {
            margin-bottom: 20px;
            page-break-inside: avoid;
            border: 1px solid #e5e7eb;
            border-radius: 4px;
            overflow: hidden;
        }

        .food-header {
            background: linear-gradient(135deg, #17c964 0%, #17c964 100%);
            color: white;
            padding: 8px 12px;
        }

        .food-header h2 {
            margin: 0;
            font-size: 13px;
            display: inline-block;
        }

        .food-info {
            font-size: 9px;
            margin-right: 12px;
            opacity: 0.95;
        }

        .food-code {
            float: left;
            background: rgba(255,255,255,0.2);
            padding: 2px 8px;
            border-radius: 3px;
            font-size: 9px;
        }

        .summary-box {
            background-color: #f8fafc;
            padding: 6px 12px;
            border-bottom: 1px solid #e5e7eb;
            font-size: 9px;
        }

        .summary-inline {
            display: inline-block;
            margin-left: 20px;
        }

        .summary-inline strong {
            color: #17c964;
            font-weight: bold;
        }

        .orders-table {
            width: 100%;
            border-collapse: collapse;
        }

        .orders-table th,
        .orders-table td {
            border: 1px solid #e5e7eb;
            padding: 5px 3px;
            text-align: center;
            font-size: 8px;
        }

        .orders-table th {
            background: linear-gradient(180deg, #64748b 0%, #475569 100%);
            color: white;
            font-weight: bold;
            font-size: 8px;
        }

        .orders-table tbody tr:nth-child(even) {
            background-color: #f8fafc;
        }

        .orders-table tbody tr:nth-child(odd) {
            background-color: #ffffff;
        }

        .price-col {
            text-align: left;
            direction: ltr;
            font-family: 'Courier New', monospace;
        }

        .total-row {
            background: linear-gradient(180deg, #dbeafe 0%, #bfdbfe 100%) !important;
            font-weight: bold;
            color: #17c964;
        }

        .no-orders {
            text-align: center;
            padding: 15px;
            color: #9ca3af;
            font-style: italic;
        }

        .page-break {
            page-break-after: always;
        }
    </style>
</head>
<body>
<div class="header">
    <h1>گزارش تفصیلی سفارشات غذا</h1>
</div>

@foreach($foods as $index => $food)
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
            <table class="orders-table">
                <thead>
                <tr>
                    <th style="width: 4%">ردیف</th>
                    <th style="width: 8%">شماره سفارش</th>
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
                    <td colspan="2">جمع کل</td>
                    <td>{{ number_format(collect($food['orders'])->sum('quantity')) }}</td>
                    <td class="price-col">-</td>
                    <td class="price-col">{{ number_format(collect($food['orders'])->sum('total_price')) }}</td>
                    <td class="price-col">{{ number_format(collect($food['orders'])->sum('tax')) }}</td>
                    <td class="price-col">{{ number_format(collect($food['orders'])->sum('rate_service')) }}</td>
                    <td class="price-col">{{ number_format(collect($food['orders'])->sum('discounted_price')) }}</td>
                    <td class="price-col">{{ number_format(collect($food['orders'])->sum('total')) }}</td>
                    <td>-</td>
                </tr>
                </tbody>
            </table>
        @else
            <div class="no-orders">هیچ سفارشی یافت نشد</div>
        @endif
    </div>

    @if(($index + 1) % 3 == 0 && !$loop->last)
        <div class="page-break"></div>
    @endif
@endforeach

</body>
</html>