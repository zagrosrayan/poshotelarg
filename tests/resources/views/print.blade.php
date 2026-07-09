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
            font-size: 12px;
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
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <div class="hotel-name">{{ $order->hotel_name ?? 'هتل ارگ' }}</div>
            <div class="printer-name">{{ $printer->location }}</div>
        </div>
    </div>

    <div class="order-info">
        <div class="order-info-row">
            <span>#{{ str_pad($order->id, 4, '0', STR_PAD_LEFT) }}</span>
            <span>
                {{ \Morilog\Jalali\Jalalian::fromDateTime($order->created_at)->format('Y/m/d H:i') }}
            </span>
        </div>
    </div>

    @if($order->desc_number)
        <div class="table-number">
            میز {{ $order->desc_number }}
        </div>
    @else
        <div class="table-number">
        اتاق {{ $order->reserve->Room }}
            </div>
    @endif

    <table class="items">
        <tr>
        <th class="description">شرح</th>
            <th class="quantity">تعداد</th>
            
        </tr>
        @foreach($order->children as $item)
        <tr>
        <td class="description">{{ optional($item->food)->name }}</td>
            <td class="quantity">{{ $item->quantity }}</td>
        
        </tr>
        @endforeach
    </table>
</body>
</html>
