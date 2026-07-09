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
    </style>
</head>
<body>
<div class="header">
    <h1>گزارش تفصیلی سفارشات غذا</h1>
</div>