<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ResidentsReportExport implements FromCollection, WithHeadings, WithStyles
{
    protected $residents;

    public function __construct($residents)
    {
        $this->residents = $residents;
    }

    public function collection()
    {
        return $this->residents;
    }

    public function headings(): array
    {
        return [
            'نام مهمان',
            'کد حساب',
            'اتاق',
            'شماره رزرو',
            'شماره پروفایل',
            'امتیاز',
            'مجموع خرید',
            'آژانس',
            'شرکت',
            'منبع',
            'گروه',
            'تاریخ ورود',
            'تاریخ خروج',
            'تاریخ چک‌این',
            'تاریخ چک‌اوت',
            'نرخ',
            'موبایل',
            'موجودی',
            'یادداشت',
            'تاریخ ایجاد',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}