<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class FoodReportExport implements FromCollection, WithHeadings, WithStyles, WithEvents
{
    protected $foods;
    protected $currentRow = 2;

    public function __construct($foods)
    {
        $this->foods = $foods;
    }

    public function collection()
    {
        $data = collect();

        foreach ($this->foods as $food) {
            $data->push([
                'نام غذا' => $food['food_name'],
                'دسته بندی' => $food['article'] ?? '-',
                'مدیر سود' => $food['profit_manager'] ?? '-',
                'تعداد سفارشات' => number_format($food['summary']['order_count']),
                'تعداد کل' => number_format($food['summary']['total_quantity']),
                'قیمت میانگین' => number_format($food['summary']['average_price']),
                'مجموع فروش' => number_format($food['summary']['total_price']),
                '' => '',
                '' => '',
                '' => '',
            ]);

            $data->push([
                'ردیف',
                'شماره فاکتور',
                'تعداد',
                'قیمت واحد',
                'قیمت کل',
                'مالیات',
                'سرویس',
                'تخفیف',
                'جمع نهایی',
                'تاریخ'
            ]);

            $orderIndex = 1;
            $totalQuantity = 0;
            $totalOrderPrice = 0;
            $totalTax = 0;
            $totalService = 0;
            $totalDiscount = 0;
            $totalFinal = 0;

            foreach ($food['orders'] as $order) {
                $data->push([
                    $orderIndex++,
                    $order['invoice_number'],
                    number_format($order['quantity']),
                    number_format($order['price']),
                    number_format($order['total_price']),
                    number_format($order['tax']),
                    number_format($order['rate_service']),
                    number_format($order['discounted_price']),
                    number_format($order['total']),
                    $order['created_at']
                ]);

                $totalQuantity += $order['quantity'];
                $totalOrderPrice += $order['total_price'];
                $totalTax += $order['tax'];
                $totalService += $order['rate_service'];
                $totalDiscount += $order['discounted_price'];
                $totalFinal += $order['total'];
            }

            $data->push([
                'جمع کل ' . $food['food_name'],
                '',
                number_format($totalQuantity),
                '-',
                number_format($totalOrderPrice),
                number_format($totalTax),
                number_format($totalService),
                number_format($totalDiscount),
                number_format($totalFinal),
                '-'
            ]);

            $data->push(['', '', '', '', '', '', '', '', '', '']);
        }

        return $data;
    }
    public function headings(): array
    {
        return ['نام غذا', 'دسته بندی', 'مدیر سود', 'تعداد سفارشات', 'تعداد کل', 'قیمت میانگین', 'مجموع فروش', '', '', ''];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->setRightToLeft(true);

        $sheet->getStyle('A1:J1')->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 12,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '4472C4'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                ],
            ],
        ]);

        foreach (range('A', 'J') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        return [];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $highestRow = $sheet->getHighestRow();

                for ($row = 2; $row <= $highestRow; $row++) {
                    $cellValue = $sheet->getCell('A' . $row)->getValue();

                    if ($cellValue && !is_numeric($cellValue) && $cellValue !== 'ردیف' && $cellValue !== 'جمع کل') {
                        $sheet->getStyle('A' . $row . ':J' . $row)->applyFromArray([
                            'font' => [
                                'bold' => true,
                                'size' => 11,
                            ],
                            'fill' => [
                                'fillType' => Fill::FILL_SOLID,
                                'startColor' => ['rgb' => 'E7E6E6'],
                            ],
                            'borders' => [
                                'allBorders' => [
                                    'borderStyle' => Border::BORDER_THIN,
                                ],
                            ],
                        ]);
                    }

                    if ($cellValue === 'ردیف') {
                        $sheet->getStyle('A' . $row . ':J' . $row)->applyFromArray([
                            'font' => [
                                'bold' => true,
                            ],
                            'fill' => [
                                'fillType' => Fill::FILL_SOLID,
                                'startColor' => ['rgb' => 'D9E1F2'],
                            ],
                            'alignment' => [
                                'horizontal' => Alignment::HORIZONTAL_CENTER,
                            ],
                            'borders' => [
                                'allBorders' => [
                                    'borderStyle' => Border::BORDER_THIN,
                                ],
                            ],
                        ]);
                    }

                    if ($cellValue === 'جمع کل') {
                        $sheet->getStyle('A' . $row . ':J' . $row)->applyFromArray([
                            'font' => [
                                'bold' => true,
                            ],
                            'fill' => [
                                'fillType' => Fill::FILL_SOLID,
                                'startColor' => ['rgb' => 'FFF2CC'],
                            ],
                            'borders' => [
                                'allBorders' => [
                                    'borderStyle' => Border::BORDER_THIN,
                                ],
                            ],
                        ]);
                    }

                    if (is_numeric($cellValue) || $cellValue === '-') {
                        $sheet->getStyle('A' . $row . ':J' . $row)->applyFromArray([
                            'borders' => [
                                'allBorders' => [
                                    'borderStyle' => Border::BORDER_THIN,
                                ],
                            ],
                        ]);
                    }
                }
            },
        ];
    }
}