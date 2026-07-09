<!DOCTYPE html>
   <html lang="fa">
   <head>
       <meta charset="UTF-8">
       <title>پرینت سفارش</title>
       <style>
           body {
               font-family: 'Vazir', sans-serif; /* استفاده از فونت فارسی */
               direction: rtl; /* راست‌چین کردن متن */
           }
       </style>
       <link href="{{ public_path('fonts/Vazir.ttf') }}" rel="stylesheet">
   </head>
   <body>
       <h1>سفارش شما</h1>
       <p>نام: {{ $name }}</p>
       <p>تاریخ: {{ $date }}</p>
       <p>مبلغ: {{ $amount }}</p>
   </body>
   </html>
