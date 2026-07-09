@php use Morilog\Jalali\Jalalian; @endphp
    <!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8"/>
    <title>رسید سفارش</title>
    <style>
        @font-face {
            font-family: 'Vazir';
            src: url('{{ public_path('font/Vazir.ttf') }}') format('truetype');
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Vazir', Tahoma, Arial;
            direction: rtl;
            text-align: right;
            font-size: 14px;
            width: 220px;
            background-color: white;
            color: black;
            line-height: 1.3;
        }

        .header {
            text-align: right;
            padding: 0 0 6px 0;
            border-bottom: 1px dashed #000;
            margin-bottom: 6px;
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .hotel-name {
            font-size: 17px;
            font-weight: bold;
            margin-bottom: 2px;
        }

        .canceled-notice {
            font-size: 20px;
            font-weight: bold;
            text-align: center;
            margin-top: 5px;
        }

        .printer-name {
            font-size: 14px;
            margin-right: 10px;
        }

        .order-info {
            padding: 6px 4px;
            border-bottom: 1px dashed #000;
            margin-bottom: 6px;
        }

        .order-info-row {
            display: flex;
            justify-content: space-between;
            font-size: 14px;
            font-weight: bold;
        }

        .table-number {
            text-align: center;
            font-size: 18px;
            font-weight: bold;
            padding: 6px 0;
            margin: 2px 0 6px 0;
        }

        .service-type {
            font-size: 14px;
            display: block;
            margin-top: 2px;
        }

        .items {
            width: 100%;
            border-collapse: collapse;
            margin-top: 2px;
        }

        .items th {
            border-bottom: 1px solid #000;
            padding: 5px 4px;
            font-size: 14px;
            font-weight: bold;
        }

        .items td {
            padding: 2px 1px;
            font-size: 12px;
            border-bottom: 1px dashed #eee;
            min-height: 32px;
            vertical-align: top;
        }

        .items tr:last-child td {
            border-bottom: none;
        }

        .quantity {
            width: 55px;
            text-align: center;
        }

        .description {
            text-align: right;
            width: 165px;
            word-wrap: break-word;
        }

        @media print {
            body {
                width: 220px;
                margin: 0;
                padding: 0;
            }
            .items td {
                font-size: 12px;
            }
        }

        .description-note {
            display: block;
            text-align: center;
        }
    </style>
</head>
<body>

<div class="header">
    <div class="header-content">
        <div class="hotel-name">{{ $order->hotel_name ?? 'هتل ارگ' }}</div>
        <div class="printer-name">{{ $printer->location }}</div>
        <div class="printer-name">{{ auth()->user()->name }}</div>
    </div>
</div>

<div class="order-info">
    <div class="order-info-row">
        <span>#{{ str_pad($order->invoice_number, 4, '0', STR_PAD_LEFT) }}</span>
        <span>{{ Jalalian::fromDateTime($order->created_at)->format('Y/m/d H:i') }}</span>
    </div>
</div>
<div class="table-number">

@switch($order->service_type)
    @case('takeaway')
        بیرون ‌بر
        @break
    @case('dine_in')
            داخل سالن
    <span class="service-type">
        {{$order->desc_number}}
    </span>
    @break
        @case('room_service')
            سرویس اتاق
            @break
@endswitch
    </div>

<table class="items">
    <tr>
        <th class="description">شرح</th>
        <th class="quantity">تعداد</th>
    </tr>
    @foreach($order->children as $item)
        <tr>
            <td class="description">
                {{ optional($item->food)->name }}
                @if($item->description)
                    <br>
                    <span class="description-note">{{ $item->description }}</span>
                @endif
            </td>
            <td class="quantity">{{ $item->quantity }}</td>
        </tr>
    @endforeach
</table>

@if(isset($order->expired_discount_info) && !empty($order->expired_discount_info))
    <div style="border-top: 1px dashed #000; padding: 5px 0; margin-top: 5px; text-align: center; font-size: 12px;">
        <div style="font-weight: bold; margin-bottom: 2px;">تخفیف منقضی شده</div>
        <div style="margin-bottom: 2px;">به علت منقضی شدن زمان تخفیف نتوانستیم تخفیف را اعمال کنیم</div>
        <div>کد: {{ $order->expired_discount_info['code'] }}</div>
        <div>
            مقدار:
            @if($order->expired_discount_info['discount_type'] == 'percentage')
                {{ $order->expired_discount_info['discount_value'] }} درصد
            @else
                {{ number_format($order->expired_discount_info['discount_value']) }} ریال
            @endif
        </div>
        <div>
            اعتبار: {{ \Morilog\Jalali\Jalalian::fromDateTime($order->expired_discount_info['starts_at'])->format('Y/m/d') }}
            تا
            {{ \Morilog\Jalali\Jalalian::fromDateTime($order->expired_discount_info['expires_at'])->format('Y/m/d') }}
        </div>
    </div>
@endif

</body>
</html>
