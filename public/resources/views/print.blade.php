<!DOCTYPE html>
<html lang="fa">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>رسید سفارش</title>
    <style>
        @font-face {
            font-family: "Vazir";
            src: url("{{ public_path('font/Vazir.ttf') }}") format("truetype");
        }
        body {
            font-family: "Vazir", sans-serif;
            direction: rtl;
            text-align: right;
            font-size: 12px;
            margin: 0;
            padding: 0;
        }
        .container {
            width: 100%;
            max-width: 280px; /* کاهش عرض برای تطابق با صفحه */
            margin: auto;
            padding: 20px; /* فاصله بیشتر از لبه‌های صفحه */
        }
        .details {
            margin: 0 15px; /* فاصله از چپ و راست */
            display: flex;
            flex-direction: column;
            align-items: flex-start;
        }
        .details p {
            margin: 5px 0;
            display: flex;
            justify-content: space-between;
            width: 100%; /* عرض 100 درصد برای نمایش درست */
        }
        .details p strong {
            flex: 1;
        }
        .details p span {
            margin-left: 5px; /* فاصله بین قیمت و کلمه تومان */
        }
        .header {
            text-align: center;
            margin-bottom: 10px;
        }
        .header h1 {
            font-size: 16px;
            margin: 0;
        }
        .header p {
            font-size: 12px;
            margin: 5px 0;
        }
        .line {
            border-top: 1px dashed black;
            margin: 10px 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            font-size: 12px;
        }
        table th, table td {
            border: 1px solid black;
            padding: 5px;
            text-align: center;
        }
        .footer {
            text-align: center;
            font-size: 10px;
            margin-top: 10px;
        }
    </style>
</head>
<body>
<div class="container">
    <!-- هدر -->
    <div class="header">
        <h1>هتل ارگ</h1>
        <p>شماره تماس: ۰۹۱۲۳۴۵۶۷۸۹</p>
        <p>آدرس: خیابان آزادی، تهران</p>
    </div>

    <div class="line"></div>

    <!-- جزئیات -->
    <div class="details">
        <p><strong>تاریخ:</strong> {{ \Morilog\Jalali\Jalalian::now()->format('Y/m/d') }}</p>
        <p><strong>شماره فاکتور:</strong> {{ $order->id }}</p>
        
        <p><strong>نام مشتری:</strong> {{ $order->customer->name ?? $order->reserve->GuestName }}</p>
    </div>

    <div class="line"></div>

    <!-- جدول سفارشات -->
    <table>
        <thead>
        <tr>
            <th>کالا</th>
            <th>تعداد</th>
            <th>قیمت</th>
            <th>واحد</th>
        </tr>
        </thead>
        <tbody>
        @foreach ($order->children as $child)
            <tr>
                <td>{{ $order->food->name }}</td>
                <td>{{ $order->quantity }}</td>
                <td>{{ $order->price }}</td>
                <td>تومان</td>
            </tr>
        @endforeach
        </tbody>
    </table>

    <div class="line"></div>

    <!-- مبلغ -->
    <div class="details">
        <p><strong>مبلغ کل:</strong> <span>{{ $order->price }} تومان</span></p>
        <p><strong>تخفیف:</strong> <span>{{ $order->discounted_price }} تومان</span></p>
        <p><strong>مبلغ پرداختی:</strong> <span>{{ $payable->total_price }} تومان</span></p>
    </div>

    <div class="line"></div>

    <!-- فوتر -->
    <div class="footer">
        <p>با آرزوی سفری خوش!</p>
        <p>هتل ارگ</p>
    </div>
</div>
</body>
</html>
