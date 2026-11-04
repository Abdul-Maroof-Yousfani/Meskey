<?php

namespace App\Http\Controllers\Export;

use App\Http\Controllers\Controller;
use App\Models\Export\ExportFiling;
use App\Models\Export\ExportFilingDocument;
use App\Models\Export\ExportOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;

class ExportFilingController extends Controller
{
    private const UPLOAD_PATH = 'uploads/export-filing';

    public function index()
    {
        return view('management.export.export-filing.index');
    }

    public function getList(Request $request)
    {
        $filings = ExportFiling::with(['exportOrder.buyer', 'createdBy'])
            ->withCount('documents')
            ->when($request->filled('search'), function ($q) use ($request) {
                $searchTerm = '%' . $request->search . '%';

                $q->whereHas('exportOrder', function ($sq) use ($searchTerm) {
                    $sq->where('voucher_no', 'like', $searchTerm)
                        ->orWhere('contract_no', 'like', $searchTerm);
                });
            })
            ->latest()
            ->paginate($request->get('per_page', 25));

        return view('management.export.export-filing.getList', compact('filings'));
    }

    public function create()
    {
        // Only export orders that are not cancelled
        $exportOrders = ExportOrder::with('buyer')
            ->where(function ($q) {
                $q->where('cancel', '!=', 1)->orWhereNull('cancel');
            })
            ->latest()
            ->get();

        return view('management.export.export-filing.create', compact('exportOrders'));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'export_order_id' => 'required|exists:export_orders,id',
            'remarks' => 'nullable|string',
            'documents' => 'required|array|min:1',
            'documents.*.name' => 'required|string|max:255',
            'documents.*.file' => 'required|file|max:20480',
        ], [
            'export_order_id.required' => 'Please select an export order.',
            'documents.required' => 'Please add at least one document.',
            'documents.*.name.required' => 'Document name is required for every row.',
            'documents.*.file.required' => 'File is required for every row.',
            'documents.*.file.max' => 'Each file must not be larger than 20MB.',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $exportOrder = ExportOrder::find($request->export_order_id);
        if ($exportOrder && $exportOrder->cancel) {
            return response()->json([
                'errors' => ['export_order_id' => ['This export order has been cancelled.']],
            ], 422);
        }

        $movedFiles = [];
        DB::beginTransaction();

        try {
            $filing = ExportFiling::create([
                'export_order_id' => $request->export_order_id,
                'remarks' => $request->remarks,
                'created_by' => auth()->user()->id,
            ]);

            $destination = public_path(self::UPLOAD_PATH);
            File::ensureDirectoryExists($destination);

            foreach ($request->documents as $index => $document) {
                $file = $request->file("documents.$index.file");

                $originalName = $file->getClientOriginalName();
                $mimeType = $file->getClientMimeType();
                $size = $file->getSize();
                $fileName = time() . '_' . uniqid() . '_' . preg_replace('/[^A-Za-z0-9._-]/', '_', $originalName);

                $file->move($destination, $fileName);
                $movedFiles[] = $destination . DIRECTORY_SEPARATOR . $fileName;

                ExportFilingDocument::create([
                    'export_filing_id' => $filing->id,
                    'document_name' => $document['name'],
                    'file_path' => self::UPLOAD_PATH . '/' . $fileName,
                    'file_name' => $originalName,
                    'file_type' => $mimeType,
                    'file_size' => $size,
                    'uploaded_by' => auth()->user()->id,
                ]);
            }

            DB::commit();

            return response()->json(['success' => 'Filing documents saved successfully.', 'data' => $filing], 201);
        } catch (\Throwable $e) {
            DB::rollBack();

            // Remove files already copied, so a failed save leaves nothing behind
            foreach ($movedFiles as $path) {
                File::delete($path);
            }

            return response()->json([
                'message' => 'Something went wrong: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function show(string $id)
    {
        $filing = ExportFiling::with(['exportOrder.buyer', 'documents', 'createdBy'])->findOrFail($id);

        return view('management.export.export-filing.show', compact('filing'));
    }

    public function destroy(string $id)
    {
        $filing = ExportFiling::with('documents')->find($id);

        if (!$filing) {
            return response()->json(['error' => 'Filing not found.'], 404);
        }

        DB::beginTransaction();

        try {
            $paths = $filing->documents->pluck('file_path')->all();

            $filing->documents()->delete();
            $filing->delete();

            DB::commit();

            foreach ($paths as $path) {
                File::delete(public_path($path));
            }

            return response()->json(['success' => 'Filing deleted successfully.']);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json(['error' => 'Failed to delete filing: ' . $e->getMessage()], 500);
        }
    }
}
