<?php

namespace App\Service;

use App\Http\Service\TypeSlug;
use App\Models\Order;
use App\Models\Printer;
use App\Models\ProfitManager;
use App\Models\Setting;
use App\Models\Type;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\View;

class PrintService
{
    public function sendOrderToPrinters(Order $order,$file = 'print')
    {
        try {
            $itemsGroupedByPrinter = $this->groupItemsByPrinter($order);

            if (empty($itemsGroupedByPrinter)) {
                return;
            }

            foreach ($itemsGroupedByPrinter as $printerId => $items) {
                $printer = Printer::find($printerId);
                if (!$printer) {
                    continue;
                }

                // Filter items for this printer
                $order->children = collect($items);

                $htmlContent = View::make($file, [
                    'order' => $order,
                    'printer' => $printer,
                ])->render();

                // Correct font path to absolute path
                $fontPath = public_path('font/Vazir.ttf');
                $htmlContent = str_replace(
                    "url('{{ public_path('font/Vazir.ttf') }}')",
                    "url('{$fontPath}')",
                    $htmlContent
                );

                // Add base tag to fix path issues
                $htmlContent = str_replace(
                    '<head>',
                    '<head><base href="file://' . public_path() . '/">',
                    $htmlContent
                );

                // Save HTML to temporary file
                $tempDir = storage_path('app/public/');
                if (!file_exists($tempDir)) {
                    mkdir($tempDir, 0777, true);
                }

                $htmlFilePath = $tempDir . '/receipt.html';
                $imagePath = $tempDir . '/receipt.png';

                file_put_contents($htmlFilePath, $htmlContent);
                chmod($htmlFilePath, 0777);

                // Log HTML content for debugging

                // Convert HTML to image
                $command = "/usr/bin/wkhtmltoimage"
                    . " --enable-local-file-access"
                    . " --format png"
                    . " --quality 100"
                    . " --width 100"
                    . " --height 600"
                    . " {$htmlFilePath}"
                    . " {$imagePath}"
                    . " 2>&1";

                $output = [];
                $returnVar = 0;
                exec($command, $output, $returnVar);
                // Log the output of the command

                if ($returnVar !== 0) {
                    throw new Exception(implode("\n", $output));
                }

                // Check if image was created
                if (!file_exists($imagePath)) {
                    throw new Exception('Image file was not created');
                }

                // chmod($imagePath, 0777);
                @unlink($htmlFilePath);
                $this->printItems($printer, $order, $imagePath);
            }
        } catch (Exception $e) {

            throw $e;
        }
    }

    private function groupItemsByPrinter(Order $order)
    {
        $itemsGroupedByPrinter = [];
        $laserPrinterType = Type::query()->where('slug', TypeSlug::THERMAL_PRINTER)->first();

        foreach ($order->children as $item) {
            $printers = $this->getPrintersForItem($item);
            foreach ($printers as $printer) {
                if ($printer && $printer->type == $laserPrinterType->id) {
                    $itemsGroupedByPrinter[$printer->id][] = $item;
                }
            }
        }

        return $this->combineItemsForPrinters($itemsGroupedByPrinter, $order);
    }

    private function isSpecialCase($slug)
    {
        return strpos($slug, 'article-type-special-') !== false;
    }

    public function printChangedItems(Order $order, array $changedItems)
    {
        try {
            // هر آیتم تغییر یافته را به پرینتر مربوطه تخصیص بده
            $itemsGroupedByPrinter = [];
            foreach (['added', 'removed', 'updated'] as $changeType) {
                foreach ($changedItems[$changeType] ?? [] as $item) {
                    // برای updated ساختار فرق داره
                    $targetItem = $changeType === 'updated' ? $item['new'] : $item;

                    // ارسال شیء به تابع getPrintersForItem
                    $printers = $this->getPrintersForItemArray($targetItem);

                    foreach ($printers as $printer) {
                        if (!isset($itemsGroupedByPrinter[$printer->id])) {
                            $itemsGroupedByPrinter[$printer->id] = [
                                'added' => [],
                                'removed' => [],
                                'updated' => [],
                            ];
                        }
                        if ($changeType === 'updated') {
                            $itemsGroupedByPrinter[$printer->id]['updated'][] = $item;
                        } else {
                            $itemsGroupedByPrinter[$printer->id][$changeType][] = $item;
                        }
                    }
                }
            }

            if (empty($itemsGroupedByPrinter)) return;

            foreach ($itemsGroupedByPrinter as $printerId => $data) {
                $printer = Printer::find($printerId);
                if (!$printer) continue;

                // فقط آیتم‌های مربوط به این پرینتر را به ویو بفرست
                $htmlContent = View::make('print-changes', [
                    'order' => $order,
                    'printer' => $printer,
                    'added' => $data['added'],
                    'removed' => $data['removed'],
                    'updated' => $data['updated'],
                ])->render();

                // تنظیم فونت و base به صورت مطمئن
                $fontPath = public_path('font/Vazir.ttf');
                $htmlContent = str_replace(
                    "url('{{ public_path('font/Vazir.ttf') }}')",
                    "url('{$fontPath}')",
                    $htmlContent
                );
                $htmlContent = str_replace(
                    '<head>',
                    '<head><base href="file://' . public_path() . '/">',
                    $htmlContent
                );

                $tempDir = storage_path('app/public/');
                if (!file_exists($tempDir)) mkdir($tempDir, 0777, true);

                $htmlFilePath = $tempDir . '/changes_' . $printer->id . '.html';
                $imagePath = $tempDir . '/changes_' . $printer->id . '.png';

                file_put_contents($htmlFilePath, $htmlContent);
                chmod($htmlFilePath, 0777);

                $command = "/usr/bin/wkhtmltoimage"
                    . " --enable-local-file-access"
                    . " --format png"
                    . " --quality 100"
                    . " --width 220"
                    . " --height 600"
                    . " {$htmlFilePath}"
                    . " {$imagePath}"
                    . " 2>&1";
                $output = [];
                $returnVar = 0;
                exec($command, $output, $returnVar);

                @unlink($htmlFilePath);

                if ($returnVar !== 0 || !file_exists($imagePath)) {
                    continue;
                }

                // فراخوانی تابع پرینت
                $this->printItems($printer, $order, $imagePath);

                // تمیزکاری
                @unlink($imagePath);
            }
        } catch (\Exception $e) {
            throw $e;
        }
    }
    private function getPrintersForItemArray($item)
    {
        $printers = [];
        $profitManagerPrinters = [];
        $laserPrinterType = Type::query()->where('slug', TypeSlug::THERMAL_PRINTER)->first();


        // Priority 1: Food specific printer
        if (isset($item['food_id'])) {
            $printer = Printer::where('food_id', $item['food_id'])->where('type', $laserPrinterType->id)->first();
            if ($printer) {
                $printers[] = $printer;
            }
        }

        // Priority 2: Profit Manager printer
        if (isset($item['food']) && $item['food']['profit_manager_id']) {
            $profitManagerPrinters = Printer::query()->where('profit_manager_id', $item['food']['profit_manager_id'])
                ->where('type', $laserPrinterType->id)->get();
            foreach ($profitManagerPrinters as $printer) {
                // Skip POS-Kitchen and POS-Kitchen2 if it's a special case
                if ($this->isSpecialCase($item['food']['article']['slug']) && in_array($printer->name, ['POS-Kitchen', 'POS-Kitchen2'])) {
                    continue;
                }
                $printers[] = $printer;
            }
        }

        // Priority 3: Article (category) printer
        if (isset($item['food']) && $item['food']['article_id']) {
            $printer = Printer::where('article_id', $item['food']['article_id'])->where('type', $laserPrinterType->id)->first();
            if ($printer) {
                // Skip POS-Kitchen and POS-Kitchen2 if it's a special case
                $printers[] = $printer;
            }
        }

        return array_filter($printers); // Remove null values
    }

    private function getPrintersForItem($item)
    {
        $printers = [];
        $profitManagerPrinters = [];


        // Priority 1: Food specific printer
        if ($item['food_id'] ) {
            $printer = Printer::where('food_id', $item->food_id)->first();
            if ($printer) {
                $printers[] = $printer;
            }
        }

        // Priority 2: Profit Manager printer
        if ($item->food && $item->food->profit_manager_id) {
            $profitManagerPrinters = Printer::query()->where('profit_manager_id', $item->food->profit_manager_id)->get();
            foreach ($profitManagerPrinters as $printer) {
                // Skip POS-Kitchen and POS-Kitchen2 if it's a special case
                if ($this->isSpecialCase($item->food->article->slug) && in_array($printer->name, ['POS-Kitchen', 'POS-Kitchen2'])) {
                    continue;
                }
                $printers[] = $printer;
            }
        }

        // Priority 3: Article (category) printer
        if ($item->food && $item->food->article_id) {
            $printer = Printer::where('article_id', $item->food->article_id)->first();
            if ($printer) {
                // Skip POS-Kitchen and POS-Kitchen2 if it's a special case
                $printers[] = $printer;
            }
        }

        return array_filter($printers); // Remove null values
    }

    private function combineItemsForPrinters($itemsGroupedByPrinter, $order)
    {
        $combinedItemsGroupedByPrinter = [];

        foreach ($itemsGroupedByPrinter as $printerId => $items) {
            if (!isset($combinedItemsGroupedByPrinter[$printerId])) {
                $combinedItemsGroupedByPrinter[$printerId] = [];
            }
            // If printer has profit manager but no article id, include all items with the same profit manager
            $printer = Printer::find($printerId);
            if ($printer && empty($printer->article_id) && $printer->profit_manager_id) {
                foreach ($order->children as $item) {
                    if ($item->food && $item->food->profit_manager_id == $printer->profit_manager_id) {
                        $combinedItemsGroupedByPrinter[$printerId][] = $item;
                    }
                }
            } else {
                foreach ($items as $item) {
                    $combinedItemsGroupedByPrinter[$printerId][] = $item;
                }
            }
        }

        // Remove duplicate items for each printer
        foreach ($combinedItemsGroupedByPrinter as $printerId => $items) {
            $combinedItemsGroupedByPrinter[$printerId] = array_unique($items, SORT_REGULAR);
        }

        // Ensure special case items don't go to POS-Kitchen or POS-Kitchen2
        foreach ($combinedItemsGroupedByPrinter as $printerId => $items) {
            $printer = Printer::find($printerId);
            if ($printer &&  empty($printer->article_id) && in_array($printer->name, ['POS-Kitchen', 'POS-Kitchen2'])) {
                $combinedItemsGroupedByPrinter[$printerId] = array_filter($items, function ($item) {
                    return !$this->isSpecialCase($item->food->article->slug);
                });
            }
        }

        return $combinedItemsGroupedByPrinter;
    }

    private function printItems(Printer $printer, Order $order, string $imagePath)
    {
        try {
            // Print with CUPS
            $this->printWithCups($printer, $imagePath);

            // Delete temporary files
            @unlink($imagePath);
        } catch (Exception $e) {

            throw $e;
        }
    }

    private function printWithCups(Printer $printer, string $imagePath)
    {
        $command = "lp -d " . escapeshellarg($printer->name) . " " . escapeshellarg($imagePath);

        $output = [];
        $returnVar = 0;
        exec($command, $output, $returnVar);
        if ($returnVar !== 0) {
            throw new Exception("Print error: " . $printer);
        }
    }

    public function printHPInvoice(Order $order, string $printerName)
    {
        try {
            $htmlContent = View::make('hp-invoice', [
                'order' => $order,
                'setting' => Setting::first()

            ])->render();

            // ذخیره HTML در فایل موقت
            $tempDir = storage_path('app/public/temp');
            if (!file_exists($tempDir)) {
                mkdir($tempDir, 0777, true);
            }

            $htmlFilePath = $tempDir . '/hp-invoice.html';
            file_put_contents($htmlFilePath, $htmlContent);
            chmod($htmlFilePath, 0777);

            // تبدیل HTML به تصویر
            $imagePath = $tempDir . '/hp-invoice.png';
            $command = "/usr/bin/wkhtmltoimage"
                . " --enable-local-file-access"
                . " --format png"
                . " --quality 500"
                . " --width 650"
                . " --height 900"
                . " {$htmlFilePath}"
                . " {$imagePath}"
                . " 2>&1";


            $output = [];
            $returnVar = 0;

            exec($command, $output, $returnVar);

            // Log the output of the command

            if ($returnVar !== 0) {
                throw new Exception("Print error: " . implode("\n", $output));
            }

            // Check if image was created
            if (!file_exists($imagePath)) {
                throw new Exception('Image file was not created');
            }

            // چاپ با CUPS
            $command = "lp -d " . escapeshellarg($printerName) . " " . escapeshellarg($imagePath) . ' -o fit-to-page -o media=A5 -o dpi=8';

            $output = [];
            $returnVar = 0;
            exec($command, $output, $returnVar);

            if ($returnVar !== 0) {
                throw new Exception("Print error: " . implode("\n", $output));
            }

            // پاک کردن فایل موقت
            @unlink($htmlFilePath);

            return true;
        } catch (Exception $e) {
  
            throw $e;
        }
    }
}
