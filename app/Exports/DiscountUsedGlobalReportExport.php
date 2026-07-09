<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class DiscountUsedGlobalReportExport implements FromCollection, WithHeadings, WithStyles
{
    protected $discounts;

    public function __construct($discounts)
    {
        $this->discounts = $discounts;
    }

    public function collection()
    {
        return $this->discounts;
    }

    public function headings(): array
    {
        return [
            'شناسه',
            'نام تخفیف',
            'کد تخفیف',
            'مقدار تخفیف',
            'نوع تخفیف',
            'تعداد استفاده',
            'مدیر سود',
            'وضعیت',
            'تاریخ شروع',
            'تاریخ پایان',
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