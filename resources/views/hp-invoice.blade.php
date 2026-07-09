@php use Morilog\Jalali\Jalalian; @endphp <!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8"/>
    <link rel="stylesheet" href="{{ public_path('font/Vazir.ttf') }}">
    <link rel="stylesheet" href="{{ public_path('bootstrap/css/bootstrap.min.css') }}">
    <style> body {
            font-family: 'Vazir', sans-serif;
            font-size: 10px;
            font-weight: bolder;
            background-color: #f8f9fa;
            padding: 3px;
        } </style>
</head>
<body>
<div class="container invoice-container p-0" style="margin-top:-10px !important;">
    <div class="row" style="display: flex; justify-content: space-between; align-items: center;">
        <div><img src="{{ asset('/images/arg-logo.png') }}" alt="Logo" style="width: 50px; height: auto;"></div>
    </div>
    <div><p>تاریخ: {{ Jalalian::fromDateTime($order->order_date)->format('Y/m/d') }}</p></div>
    <table class="table table-bordered items-table table-sm text-center p-0">
        <tbody>
        <tr>
            <td><strong>نام مشتری:</strong></td>
            <td>{{ $order->customer->name ?? $order->reserve->GuestName }}</td>
            <td><strong>روش پرداخت:</strong></td>
            <td>{{ optional($order->paymentMethod)->name }}</td>
            <td><strong>صندوق دار:</strong></td>
            <td>{{ optional($order->user)->name }}</td>
        </tr>
        <tr> @switch($order->service_type)
                @case('takeaway')
                    <td><strong>بیرون بر</strong></td> @break @case('dine_in')
                    <td><strong>داخل سالن</strong></td>
                    <td>{{ $order->desc_number }}</td> @break @case('room_service')
                    <td><strong>شماره اتاق</strong></td>
                    <td>{{ $order->reserve->Room ?? 'بدون اتاق' }}</td> @break @default
                    <td><strong>داخل سالن</strong></td>
                    <td>{{ $order->desc_number ?? 'بدون شماره میز' }}</td> @break
            @endswitch
            <td><strong>شماره فاکتور:</strong></td>
            <td>{{ $order->invoice_number . '#' }}</td>

            <td><strong>اسم شرکت</strong></td>
            <td>{{ $order?->reserve?->company ?? '' }}</td>
        </tr>
        </tbody>
    </table>
    <table class="table table-striped items-table table-sm mb-0">
        <thead>
        <tr>
            <th>ردیف</th>
            <th>شرح کالا</th>
            <th>تعداد</th>
            <th>فی (ریال)</th>
            <th>جمع (ریال)</th>
        </tr>
        </thead>
        <tbody> @foreach($order->children as $index => $item)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ optional($item->food)->name }}</td>
                <td>{{ $item->quantity }}</td>
                <td>{{ number_format($item->food->price, 0, '', ',') }}</td>
                <td>{{ number_format( $item->price, 0, '', ',') }}</td>
            </tr>
        @endforeach </tbody>
    </table>
    <div class="total-section text-center rounded p-0 mb-1">
        <table class="table table-borderless">
            <tr>
                <td>جمع کل بعد از تخفیف:</td>
                <td>{{ number_format($order->price, 0, '', ',') }}ریال</td>
            </tr>
            <tr>
                <td>جمع کل قبل از تخفیف:</td>
                <td>{{ number_format($order->product_price, 0, '', ',') }}ریال</td>
            </tr> @if(!empty($order->rate_service))
                <tr>
                    <td>حق سرویس ({{ $setting->rate_service }}%):</td>
                    <td>{{ number_format($order->rate_service, 0, '', ',') }}ریال</td>
                </tr>
            @endif
            <tr>
                <td>مالیات ({{ $setting->tax }}%):</td>
                <td>{{ number_format($order->tax, 0, '', ',') }}ریال</td>
            </tr>
            <tr>
                <td>تخفیف:</td>
                <td>{{ number_format($order->discounted_price, 0, '', ',') }}ریال</td>
            </tr>
            <tr>
                <td><strong>مبلغ قابل پرداخت:</strong></td>
                <td><strong>{{ number_format($order->total_price, 0, '', ',') }}ریال</strong></td>
            </tr>
        </table>
    </div> @if(isset($order->expired_discount_info) && !empty($order->expired_discount_info))
        <div class="alert alert-warning text-center p-1 mb-1" style="font-size: 10px;"><strong>تخفیف منقضی شده:</strong>
            به علت منقضی شدن زمان تخفیف، نتوانستیم کد <strong>{{ $order->expired_discount_info['code'] }}</strong> را
            اعمال کنیم. <br> مقدار: @if($order->expired_discount_info['discount_type'] == 'percentage')
                {{ $order->expired_discount_info['discount_value'] }}%
            @else
                {{ number_format($order->expired_discount_info['discount_value']) }} ریال
            @endif -
            اعتبار: {{ \Morilog\Jalali\Jalalian::fromDateTime($order->expired_discount_info['starts_at'])->format('Y/m/d') }}
            تا {{ \Morilog\Jalali\Jalalian::fromDateTime($order->expired_discount_info['expires_at'])->format('Y/m/d') }}
        </div>
    @endif
    <div class="footer text-center"><p class="mb-0">تشکر از شما برای انتخاب هتل ارگ</p></div>
</div>
</body>
</html>