<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\Master\LabourRateRequest;
use App\Models\BagPacking;
use App\Models\Category;
use App\Models\Master\ArrivalLocation;
use App\Models\Master\LabourRate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\IOFactory;

class LabourRateController extends Controller
{
    public function index() {
        return view("management.master.labour-rate.index");
    }

    public function getList(Request $request) {
        $labourRates = LabourRate::when($request->filled('search'), function ($q) use ($request) {
            $searchTerm = '%' . $request->search . '%';
            return $q->where(function ($sq) use ($searchTerm) {
                $sq->where('rate', 'like', $searchTerm);
            });
        })
            ->latest()
            ->paginate(request('per_page', 25));

        return view('management.master.labour-rate.getList', compact('labourRates'));
    }

    public function edit(LabourRate $labourRate) {

        $bag_packings = BagPacking::all();
        $categories = Category::where("category_type", "raw_finish")->get();
        $factories = ArrivalLocation::all();

        return view("management.master.labour-rate.edit", compact("labourRate", "bag_packings", "categories", "factories"));
    }

    public function create() {
        $bag_packings = BagPacking::all();
        $categories = Category::where("category_type", "raw_finish")->get();
        $factories = ArrivalLocation::all();

        return view("management.master.labour-rate.create", compact("bag_packings", "categories", "factories"));
    }

    public function store(LabourRateRequest $request) {
        try {
            $labour_rate_exists = LabourRate::where("bag_packing_id", $request->bag_packing)
                                        ->where("category_id", $request->category_id)
                                        ->where("factory_id", $request->factory_id)
                                        ->where("rate", $request->rate)
                                        ->exists();
            if($labour_rate_exists) {
                return response()->json("This combination is already created", 403);
            }
            
            LabourRate::create([
                "rate" => $request->rate,
                "bag_packing_id" => $request->bag_packing,
                "category_id" => $request->category_id,
                "factory_id" => $request->factory_id,
                "company_id" => $request->company_id,
                "status" => "active"
            ]);
        } catch(\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }

        return response()->json("Labour rate has been created!");
    }

    public function update(LabourRateRequest $request, LabourRate $labourRate) {
        try {
            $labour_rate_exists = LabourRate::where("bag_packing_id", $request->bag_packing)
                                        ->where("category_id", $request->category_id)
                                        ->where("factory_id", $request->factory_id)
                                        ->where("rate", $request->rate)
                                        ->where("id", "!=", $labourRate->id)
                                        ->exists();
            if($labour_rate_exists) {
                return response()->json("This combination is already created", 403);
            }
            
            $labourRate->update([
                "rate" => $request->rate,
                "bag_packing_id" => $request->bag_packing,
                "category_id" => $request->category_id,
                "factory_id" => $request->factory_id,
                "company_id" => $request->company_id,
                "status" => "active"
            ]);

            return response()->json("Labour Rate has been created");
        } catch(\Exception $e) {
            return response()->json($e->getMessage());
        }
    }

    public function destroy(LabourRate $labourRate) {
        $labourRate->delete();
        return response()->json("Labour rate has been deleted!");
    }

    public function importModal()
    {
        return view("management.master.labour-rate.import_modal");
    }

    public function downloadSample()
    {
        $spreadsheet = new Spreadsheet();

        // Sheet 1: Sample Data
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Labour Rates');

        $headers = ['Rate', 'Packing', 'Commodity', 'Factory / Location', 'Description'];
        $sheet->fromArray([$headers], null, 'A1');

        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => '1B84FF']
            ],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
            ]
        ];
        $sheet->getStyle('A1:E1')->applyFromArray($headerStyle);
        $sheet->getRowDimension(1)->setRowHeight(25);

        // Fetch sample data from DB
        $samplePackings = BagPacking::pluck('name')->toArray();
        $sampleCategories = Category::where('category_type', 'raw_finish')->pluck('name')->toArray();
        if (empty($sampleCategories)) {
            $sampleCategories = Category::pluck('name')->toArray();
        }
        $sampleFactories = ArrivalLocation::pluck('name')->toArray();

        $rows = [
            [12.00, $samplePackings[0] ?? '50 kg', $sampleCategories[0] ?? 'Irri-6', $sampleFactories[0] ?? 'A-45', 'Standard loading rate'],
            [10.50, $samplePackings[1] ?? '25 kg', $sampleCategories[1] ?? 'Super', $sampleFactories[1] ?? 'A-43', 'Regular rate'],
            [8.00, $samplePackings[2] ?? '5 kg', $sampleCategories[2] ?? 'Basmati Brown', $sampleFactories[2] ?? 'Larkana Location', 'Small bag rate'],
        ];
        $sheet->fromArray($rows, null, 'A2');

        foreach (range('A', 'E') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Sheet 2: Reference Data
        $refSheet = $spreadsheet->createSheet();
        $refSheet->setTitle('Reference Data');

        $refHeaders = ['Available Packings', 'Available Commodities', 'Available Factories / Locations'];
        $refSheet->fromArray([$refHeaders], null, 'A1');

        $refHeaderStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => '28A745']
            ],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
            ]
        ];
        $refSheet->getStyle('A1:C1')->applyFromArray($refHeaderStyle);
        $refSheet->getRowDimension(1)->setRowHeight(25);

        $maxCount = max(count($samplePackings), count($sampleCategories), count($sampleFactories));
        $refRows = [];
        for ($i = 0; $i < $maxCount; $i++) {
            $refRows[] = [
                $samplePackings[$i] ?? '',
                $sampleCategories[$i] ?? '',
                $sampleFactories[$i] ?? ''
            ];
        }
        if (!empty($refRows)) {
            $refSheet->fromArray($refRows, null, 'A2');
        }

        foreach (range('A', 'C') as $col) {
            $refSheet->getColumnDimension($col)->setAutoSize(true);
        }

        $spreadsheet->setActiveSheetIndex(0);

        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, 'labour_rates_sample.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0',
        ]);
    }

    public function import(Request $request)
    {
        $file = $request->file('file');
        if (!$file || !$file->isValid()) {
            return response()->json([
                'success' => false,
                'message' => 'Please upload a valid Excel or CSV file.'
            ], 422);
        }
        $extension = strtolower($file->getClientOriginalExtension());
        if (!in_array($extension, ['xlsx', 'xls', 'csv', 'txt'])) {
            return response()->json([
                'success' => false,
                'message' => 'Only .xlsx, .xls, or .csv files are supported.'
            ], 422);
        }

        $companyId = $request->company_id ?: (session('company_id') ?: (auth()->user()->current_company_id ?? 1));

        try {
            $file = $request->file('file');
            $spreadsheet = IOFactory::load($file->getRealPath());
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray(null, true, true, false);

            if (empty($rows) || count($rows) <= 1) {
                return response()->json([
                    'success' => false,
                    'message' => 'The uploaded file is empty or contains no data rows.'
                ], 422);
            }

            // Prepare lookup maps (both by lowercase name, without-spaces name, and by ID)
            $allPackings = BagPacking::all();
            $packingMap = [];
            foreach ($allPackings as $p) {
                $packingMap[strtolower(trim($p->name))] = $p->id;
                $packingMap[str_replace(' ', '', strtolower(trim($p->name)))] = $p->id;
                $packingMap[(string)$p->id] = $p->id;
            }

            $allCategories = Category::all();
            $categoryMap = [];
            foreach ($allCategories as $c) {
                $categoryMap[strtolower(trim($c->name))] = $c->id;
                $categoryMap[str_replace(' ', '', strtolower(trim($c->name)))] = $c->id;
                $categoryMap[(string)$c->id] = $c->id;
            }

            $allFactories = ArrivalLocation::all();
            $factoryMap = [];
            foreach ($allFactories as $f) {
                $factoryMap[strtolower(trim($f->name))] = $f->id;
                $factoryMap[str_replace(' ', '', strtolower(trim($f->name)))] = $f->id;
                $factoryMap[(string)$f->id] = $f->id;
            }

            // Determine column indexes from header row (row 0)
            $headers = $rows[0];
            $colMap = [
                'rate' => null,
                'bag_packing' => null,
                'category' => null,
                'factory' => null,
                'description' => null,
            ];

            foreach ($headers as $idx => $headerText) {
                $h = strtolower(trim((string)$headerText));
                $h = str_replace(['_', '-', '/', '\\'], ' ', $h);

                if ($colMap['rate'] === null && str_contains($h, 'rate')) {
                    $colMap['rate'] = $idx;
                } elseif ($colMap['bag_packing'] === null && (str_contains($h, 'pack') || str_contains($h, 'bag'))) {
                    $colMap['bag_packing'] = $idx;
                } elseif ($colMap['category'] === null && (str_contains($h, 'commo') || str_contains($h, 'categ') || str_contains($h, 'item'))) {
                    $colMap['category'] = $idx;
                } elseif ($colMap['factory'] === null && (str_contains($h, 'fact') || str_contains($h, 'locat') || str_contains($h, 'arriv'))) {
                    $colMap['factory'] = $idx;
                } elseif ($colMap['description'] === null && (str_contains($h, 'desc') || str_contains($h, 'remark') || str_contains($h, 'note'))) {
                    $colMap['description'] = $idx;
                }
            }

            // Fallbacks if headers were not named exactly
            if ($colMap['rate'] === null) $colMap['rate'] = 0;
            if ($colMap['bag_packing'] === null) $colMap['bag_packing'] = 1;
            if ($colMap['category'] === null) $colMap['category'] = 2;
            if ($colMap['factory'] === null) $colMap['factory'] = 3;
            if ($colMap['description'] === null) $colMap['description'] = 4;

            $createdCount = 0;
            $updatedCount = 0;
            $errors = [];

            DB::beginTransaction();

            for ($i = 1; $i < count($rows); $i++) {
                $row = $rows[$i];
                $rowNumber = $i + 1;

                // Check if row is completely blank
                $nonEmptyCells = array_filter($row, fn($cell) => $cell !== null && trim((string)$cell) !== '');
                if (empty($nonEmptyCells)) {
                    continue;
                }

                $rawRate = trim((string)($row[$colMap['rate']] ?? ''));
                $rawPacking = trim((string)($row[$colMap['bag_packing']] ?? ''));
                $rawCategory = trim((string)($row[$colMap['category']] ?? ''));
                $rawFactory = trim((string)($row[$colMap['factory']] ?? ''));
                $rawDesc = trim((string)($row[$colMap['description']] ?? ''));

                // Validate Rate
                if ($rawRate === '' || !is_numeric($rawRate)) {
                    $errors[] = "Row {$rowNumber}: Rate is required and must be a valid number (got '{$rawRate}').";
                    continue;
                }
                $rate = (float)$rawRate;

                // Match Packing
                $normPacking = strtolower($rawPacking);
                $normPackingNoSpace = str_replace(' ', '', $normPacking);
                $packingId = $packingMap[$normPacking] ?? ($packingMap[$normPackingNoSpace] ?? null);
                if (!$packingId) {
                    $errors[] = "Row {$rowNumber}: Packing '{$rawPacking}' not found in database.";
                    continue;
                }

                // Match Category
                $normCategory = strtolower($rawCategory);
                $normCategoryNoSpace = str_replace(' ', '', $normCategory);
                $categoryId = $categoryMap[$normCategory] ?? ($categoryMap[$normCategoryNoSpace] ?? null);
                if (!$categoryId) {
                    $errors[] = "Row {$rowNumber}: Commodity '{$rawCategory}' not found in database.";
                    continue;
                }

                // Match Factory
                $normFactory = strtolower($rawFactory);
                $normFactoryNoSpace = str_replace(' ', '', $normFactory);
                $factoryId = $factoryMap[$normFactory] ?? ($factoryMap[$normFactoryNoSpace] ?? null);
                if (!$factoryId) {
                    $errors[] = "Row {$rowNumber}: Factory/Location '{$rawFactory}' not found in database.";
                    continue;
                }

                $status = 'active';
                $description = $rawDesc ?: null;

                // Update existing record if combination already exists, or create new
                $existing = LabourRate::where('bag_packing_id', $packingId)
                    ->where('category_id', $categoryId)
                    ->where('factory_id', $factoryId)
                    ->first();

                if ($existing) {
                    $existing->update([
                        'rate' => $rate,
                        'status' => $status,
                        'description' => $description ?: $existing->description,
                        'company_id' => $companyId,
                    ]);
                    $updatedCount++;
                } else {
                    LabourRate::create([
                        'rate' => $rate,
                        'bag_packing_id' => $packingId,
                        'category_id' => $categoryId,
                        'factory_id' => $factoryId,
                        'status' => $status,
                        'description' => $description,
                        'company_id' => $companyId,
                    ]);
                    $createdCount++;
                }
            }

            DB::commit();

            $totalProcessed = $createdCount + $updatedCount;
            $msg = "Import processed successfully! {$totalProcessed} labour rate(s) imported ({$createdCount} created, {$updatedCount} updated).";
            if (!empty($errors)) {
                $msg .= " " . count($errors) . " row(s) had errors.";
            }

            return response()->json([
                'success' => true,
                'message' => $msg,
                'created_count' => $createdCount,
                'updated_count' => $updatedCount,
                'error_count' => count($errors),
                'errors' => $errors
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error importing file: ' . $e->getMessage()
            ], 500);
        }
    }
}
