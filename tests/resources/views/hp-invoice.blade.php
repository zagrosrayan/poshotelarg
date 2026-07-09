<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8"/>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="{{ public_path('font/Vazir.css') }}">
    <style>
        @page {
            size: A5;
            margin: 0;
        }
        body {
            font-family: 'Vazir', sans-serif;
            font-size: 10px;
            background-color: #f8f9fa;
            padding: 10px;
        }
    </style>
</head>
<body>
    <div class="container invoice-container mt-0">
        <div class="text-center mb-2">
            <h4 class="mb-0">فاکتور</h4>
            <p class="lead">هتل ارگ</p>
        </div>

        <table class="table table-bordered items-table">
            <tbody>
                <tr>
                    <td><strong>نام مشتری:</strong></td>
                    <td>{{ $order->customer->name ?? $order->reserve->GuestName }}</td>
                    <td><strong>مجموع قیمت:</strong></td>
                    <td>{{ number_format($order->total_price) }}تومان</td>
                </tr>
                <tr>
                    <td><strong>تخفیف:</strong></td>
                    <td>{{ number_format($order->discounted_price) }}تومان</td>
                    <td><strong>تعداد:</strong></td>
                    <td>{{ $order->quantity }}</td>
                </tr>
                <tr>
                    <td><strong>روش پرداخت:</strong></td>
                    <td>{{ optional($order->paymentMethod)->name }}</td>
                </tr>
                <tr>
                    <td><strong>شماره اتاق:</strong></td>
                    <td>{{ $order->reserve->Room ?? 'بدون اتاق' }}</td>
                    <td><strong>تاریخ:</strong></td>
                    <td>{{ \Morilog\Jalali\Jalalian::fromDateTime($order->order_date)->format('Y/m/d') }}</td>
                </tr>
                <tr>
                    <td><strong>شماره میز:</strong></td>
                    <td>{{ $order->desc_number ?? 'بدون شماره میز' }}</td>
                </tr>
            </tbody>
        </table>

        <table class="table table-striped items-table table-sm">
            <thead>
                <tr>
                    <th>ردیف</th>
                    <th>شرح کالا</th>
                    <th>تعداد</th>
                    <th>فی (تومان)</th>
                    <th>جمع (تومان)</th>
                </tr>
            </thead>
            <tbody>
                @foreach($order->children as $index => $item)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ optional($item->food)->name }}</td>
                    <td>{{ $item->quantity }}</td>
                    <td>{{ number_format($item->price) }}</td>
                    <td>{{ number_format($item->quantity * $item->price) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <div class="total-section text-right bg-light rounded p-2 mb-3">
            <table class="table table-borderless">
                <tr>
                    <td>جمع کل:</td>
                    <td>{{ number_format($order->price) }} تومان</td>
                </tr>
                @if(!empty($order->rate_service))
                <tr>
                    <td>حق سرویس ({{ $order->rate_service }}%):</td>
                    <td>{{ number_format(($order->price * $order->rate_service) / 100) }} تومان</td>
                </tr>
                @endif
                <tr>
                    <td>مالیات ({{ $order->tax }}%):</td>
                    <td>{{ number_format(($order->price * $order->tax) / 100) }} تومان</td>
                </tr>
                <tr>
                    <td><strong>مبلغ قابل پرداخت:</strong></td>
                    <td><strong>{{ number_format($order->total_price) }} تومان</strong></td>
                </tr>
            </table>
        </div>
        <div class="footer text-center">
            <p class="mb-0">تشکر از شما برای انتخاب هتل ارگ</p>
        </div>
    </div>
</body>
</html>