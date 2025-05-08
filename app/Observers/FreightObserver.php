<?php

namespace App\Observers;

use App\Models\Arrival\Freight;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FreightObserver
{
    const MAX_IMAGE_WIDTH = 1200;
    const MAX_IMAGE_HEIGHT = 1200;
    const IMAGE_QUALITY = 75;

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

                $file = request()->file($field);
                $extension = strtolower($file->getClientOriginalExtension());

                if ($extension === 'pdf') {
                    $path = 'storage/' . $this->storeFreightFile($file, $field);
                } else {
                    $path = 'storage/' . $this->optimizeAndStoreImage($file, $field, $extension);
                }

                $freight->$field = $path;
            }
        }
    }

    /**
     * Optimize and store image files using GD library with orientation fix
     */
    protected function optimizeAndStoreImage($file, $fieldName, $extension)
    {
        $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $sluggedName = Str::slug($originalName);
        $filename = 'freight-' . $fieldName . '-' . $sluggedName . '-' . now()->format('YmdHis') . '.' . $extension;
        // $path = 'freight_documents/' . $filename;
        // $filePath = $file->getRealPath();

        $directory = public_path('storage/freight_documents');
        if (!file_exists($directory)) {
            mkdir($directory, 0755, true);
        }

        $path = $directory . '/' . $filename;
        $filePath = $file->getRealPath();

        list($originalWidth, $originalHeight, $type) = getimagesize($filePath);

        switch ($type) {
            case IMAGETYPE_JPEG:
                $image = imagecreatefromjpeg($filePath);
                break;
            case IMAGETYPE_PNG:
                $image = imagecreatefrompng($filePath);
                break;
            default:
                return $file->storeAs('freight_documents', $filename, 'public');
        }

        if (function_exists('exif_read_data') && $type == IMAGETYPE_JPEG) {
            $exif = @exif_read_data($filePath);
            if (!empty($exif['Orientation'])) {
                switch ($exif['Orientation']) {
                    case 3:
                        $image = imagerotate($image, 180, 0);
                        break;
                    case 6:
                        $image = imagerotate($image, -90, 0);
                        list($originalWidth, $originalHeight) = [$originalHeight, $originalWidth];
                        break;
                    case 8:
                        $image = imagerotate($image, 90, 0);
                        list($originalWidth, $originalHeight) = [$originalHeight, $originalWidth];
                        break;
                }
            }
        }

        $ratio = $originalWidth / $originalHeight;

        if ($ratio > 1) {
            $newWidth = min($originalWidth, self::MAX_IMAGE_WIDTH);
            $newHeight = $newWidth / $ratio;
        } else {
            $newHeight = min($originalHeight, self::MAX_IMAGE_HEIGHT);
            $newWidth = $newHeight * $ratio;
        }

        $newImage = imagecreatetruecolor($newWidth, $newHeight);

        if ($type == IMAGETYPE_PNG) {
            imagecolortransparent($newImage, imagecolorallocatealpha($newImage, 0, 0, 0, 127));
            imagealphablending($newImage, false);
            imagesavealpha($newImage, true);
        }

        imagecopyresampled(
            $newImage,
            $image,
            0,
            0,
            0,
            0,
            $newWidth,
            $newHeight,
            $originalWidth,
            $originalHeight
        );

        // switch ($type) {
        //     case IMAGETYPE_JPEG:
        //         imagejpeg($newImage, Storage::disk('public')->path($path), self::IMAGE_QUALITY);
        //         break;
        //     case IMAGETYPE_PNG:
        //         imagepng($newImage, Storage::disk('public')->path($path), round(9 * self::IMAGE_QUALITY / 100));
        //         break;
        // }

        switch ($type) {
            case IMAGETYPE_JPEG:
                imagejpeg($newImage, $path, self::IMAGE_QUALITY);
                break;
            case IMAGETYPE_PNG:
                imagepng($newImage, $path, round(9 * self::IMAGE_QUALITY / 100));
                break;
        }

        imagedestroy($image);
        imagedestroy($newImage);

        // return $path;
        return 'freight_documents/' . $filename;
    }

    /**
     * Store freight related files (for PDFs)
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
        // $storagePath = str_replace('storage/', '', $filePath);
        // if (Storage::disk('public')->exists($storagePath)) {
        //     Storage::disk('public')->delete($storagePath);
        // }
        $path = public_path('storage/' . str_replace('storage/', '', $filePath));
        if (file_exists($path)) {
            unlink($path);
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
