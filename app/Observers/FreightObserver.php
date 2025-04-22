<?php

namespace App\Observers;

use App\Models\Arrival\Freight;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FreightObserver
{
    /**
     * Handle the Freight "creating" event.
     */
    public function creating(Freight $freight)
    {
        $this->handleFileUploads($freight);
    }

    /**
     * Handle the Freight "updating" event.
     */
    public function updating(Freight $freight)
    {
        $this->handleFileUploads($freight);
    }

    /**
     * Handle all file uploads for the Freight model
     */
    protected function handleFileUploads(Freight $freight)
    {
        $fileFields = [
            'bilty_document',
            'loading_weight_document',
            'other_document',
            'other_document_2'
        ];

        foreach ($fileFields as $field) {
            if (request()->hasFile($field)) {
                if ($freight->$field) {
                    $this->deleteFile($freight->$field);
                }

                $freight->$field = 'storage/' . $this->storeFreightFile(request()->file($field), $field);
            }
        }

        // $freight->difference = $freight->loaded_weight - $freight->arrived_weight;
        // $freight->net_freight = $freight->freight_per_ton * ($freight->loaded_weight / 1000);
    }

    /**
     * Store freight related files with proper naming
     */
    protected function storeFreightFile($file, $fieldName)
    {
        $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $sluggedName = Str::slug($originalName);
        $filename = 'freight-' . $fieldName . '-' . $sluggedName . '-' . now()->format('YmdHis') . '.' . $file->getClientOriginalExtension();

        return $file->storeAs('freight_documents', $filename, 'public');
    }

    /**
     * Delete file from storage
     */
    protected function deleteFile($filePath)
    {
        $storagePath = str_replace('storage/', '', $filePath);
        if (Storage::disk('public')->exists($storagePath)) {
            Storage::disk('public')->delete($storagePath);
        }
    }

    /**
     * Handle the Freight "deleted" event.
     */
    public function deleted(Freight $freight)
    {
        $fileFields = [
            'bilty_document',
            'loading_weight_document',
            'other_document',
            'other_document_2'
        ];

        foreach ($fileFields as $field) {
            if ($freight->$field) {
                $this->deleteFile($freight->$field);
            }
        }
    }
}
