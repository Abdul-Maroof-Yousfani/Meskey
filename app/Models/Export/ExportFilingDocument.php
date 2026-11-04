<?php

namespace App\Models\Export;

use Illuminate\Database\Eloquent\Model;

class ExportFilingDocument extends Model
{
    protected $fillable = [
        'export_filing_id',
        'document_name',
        'file_path',
        'file_name',
        'file_type',
        'file_size',
        'uploaded_by',
    ];

    public function filing()
    {
        return $this->belongsTo(ExportFiling::class, 'export_filing_id');
    }
}
