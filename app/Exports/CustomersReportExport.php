<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class CustomersReportExport implements FromCollection, WithHeadings, WithStyles
{
    protected $customers;

    public function __construct($customers)
    {
        $this->customers = $customers;
    }

    public function collection()
    {
        return $this->customers;
    }

    public function headings(): array
    {
        return [
            'شناسه',
            'نام',
            'شماره تماس',
            'آدرس',
            'شهر',
            'امتیاز',
            'تعداد سفارشات تکمیل شده',
            'مجموع سفارشات تکمیل شده',
            'تعداد سفارشات در انتظار',
            'مجموع سفارشات در انتظار',
            'آخرین سفارش',
            'تاریخ عضویت',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}