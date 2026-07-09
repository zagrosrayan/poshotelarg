@php use Morilog\Jalali\Jalalian; @endphp
    <!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8"/>
    <title>اصلاحیه فاکتور</title>
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
            line-height: 1.3;
            font-weight: 500;
        }

        .header {
            text-align: right;
            padding: 0 0 6px 0;
            border-bottom: 1px dashed;
            margin-bottom: 6px;
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .hotel-name {
            font-size: 17px;
            font-weight: 700;
            margin-bottom: 2px;
        }

        .edit-title {
            font-size: 20px;
            font-weight: 700;
            text-align: right;
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 5px;
        }

        .invoice-number {
            font-size: 16px;
            font-weight: 700;
            margin-right: 10px;
        }

        .printer-name {
            font-size: 14px;
            font-weight: 500;
            margin-right: 10px;
        }

        .order-info {
            padding: 6px 4px;
            border-bottom: 1px dashed;
            margin-bottom: 6px;
        }

        .order-info-row {
            display: flex;
            justify-content: space-between;
            font-size: 14px;
            font-weight: 700;
        }

        .table-number {
            text-align: center;
            font-size: 18px;
            font-weight: 700;
            padding: 6px 0;
            margin: 2px 0 6px 0;
        }

        .service-type {
            font-size: 14px;
            display: block;
            margin-top: 2px;
            font-weight: 500;
        }

        .items {
            width: 100%;
            border-collapse: collapse;
            margin-top: 2px;
        }

        .items th {
            border-bottom: 1px solid;
            padding: 5px 4px;
            font-size: 14px;
            font-weight: 700;
        }

        .items td {
            padding: 2px 1px;
            font-size: 12px;
            border-bottom: 1px dashed;
            min-height: 32px;
            vertical-align: top;
            font-weight: 500;
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

        .removed, .added, .updated {
            font-weight: 700;
            font-size: 13px;
        }

        .description-note {
            display: block;
            text-align: center;
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
            * {
                color: black !important;
                background: white !important;
            }
        }
    </style>
</head>
<body>

<div class="header">
    <div class="edit-title">
        اصلاحیه فاکتور
        <span class="invoice-number">#{{ str_pad($order->invoice_number, 4, '0', STR_PAD_LEFT) }}</span>
    </div>
    <div class="header-content">
        <div class="hotel-name">{{ $order->hotel_name ?? 'هتل ارگ' }}</div>
        <div class="printer-name">{{ $printer->location  ?? ''}}</div>
        <div class="printer-name">{{ auth()->user()->name }}</div>
    </div>
</div>

<div class="order-info">
    <div class="order-info-row">
        <span>تاریخ: {{ Jalalian::fromDateTime($order->created_at)->format('Y/m/d H:i') }}</span>
    </div>
</div>

<table class="items">
    <tr>
        <th class="description">شرح</th>
        <th class="quantity">تعداد</th>
        <th>تغییرات</th>
    </tr>
    {{-- حذف شده‌ها --}}
    @if(!empty($removed))
        @foreach($removed as $item)
            <tr>
                <td class="description">
                    {{ $item['food_name'] ?? '' }}
                    @if($item['description'])
                        <br>
                        <span class="description-note">{{ $item['description'] }}</span>
                    @endif
                </td>
                <td class="quantity">{{ $item['quantity'] }}</td>
                <td class="removed">حذف شده</td>
            </tr>
        @endforeach
    @endif

    {{-- اضافه شده‌ها --}}
    @if(!empty($added))
        @foreach($added as $item)
            <tr>
                <td class="description">
                    {{ $item['food_name'] ?? '' }}
                    @if($item['description'])
                        <br>
                        <span class="description-note">{{ $item['description'] }}</span>
                    @endif
                </td>
                <td class="quantity">{{ $item['quantity'] }}</td>
                <td class="added">اضافه شده</td>
            </tr>
        @endforeach
    @endif

    {{-- ویرایش شده‌ها --}}
    @if(!empty($updated))
        @foreach($updated as $change)
            <tr>
                <td class="description">
                    {{ $change['new']['food_name'] ?? '' }}
                    @if($change['new']['description'])
                        <br>
                        <span class="description-note">{{ $change['new']['description'] }}</span>
                    @endif
                </td>
                <td class="quantity">
                    <span style="text-decoration:line-through;color:#b71c1c;">{{ $change['old']['quantity'] }}</span>
                    <span>&rarr;</span>
                    <span>{{ $change['new']['quantity'] }}</span>
                </td>
                <td class="updated">
                    @if($change['old']['quantity'] != $change['new']['quantity'])
                        تغییر تعداد
                    @endif
                    @if($change['old']['description'] != $change['new']['description'])
                        {{ $change['old']['quantity'] != $change['new']['quantity'] ? '، ' : '' }}تغییر توضیحات
                    @endif
                    ویرایش شده
                </td>
            </tr>
        @endforeach
    @endif
</table>

</body>
</html>
