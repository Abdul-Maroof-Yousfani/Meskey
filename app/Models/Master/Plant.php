<?php

namespace App\Models\Master;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use App\Models\Acl\Company;
use App\Models\Master\ArrivalLocation;
use App\Models\Master\CompanyLocation;

class Plant extends Model
{
    use SoftDeletes;


    protected $fillable = [
        'company_id',
        'company_location_id',
        'arrival_location_id',
        'name',
        'description',
        'production_labour_charges_per_kg',
        'status',
        'auto_analysis_enabled',
        'require_last_completed',
        'min_analysis_interval',
        'max_analysis_interval',
        'input_min_interval',
        'input_max_interval',
        'output_min_interval',
        'output_max_interval',
        'next_input_analysis_at',
        'next_output_analysis_at',
    ];

    protected $casts = [
        'auto_analysis_enabled' => 'boolean',
        'require_last_completed' => 'boolean',
        'min_analysis_interval' => 'integer',
        'max_analysis_interval' => 'integer',
        'input_min_interval' => 'integer',
        'input_max_interval' => 'integer',
        'output_min_interval' => 'integer',
        'output_max_interval' => 'integer',
        'next_input_analysis_at' => 'datetime',
        'next_output_analysis_at' => 'datetime',
    ];

    public function getInputMinInterval(): int
    {
        return $this->input_min_interval ?: ($this->min_analysis_interval ?: 20);
    }

    public function getInputMaxInterval(): int
    {
        return $this->input_max_interval ?: ($this->max_analysis_interval ?: 60);
    }

    public function getOutputMinInterval(): int
    {
        return $this->output_min_interval ?: ($this->min_analysis_interval ?: 20);
    }

    public function getOutputMaxInterval(): int
    {
        return $this->output_max_interval ?: ($this->max_analysis_interval ?: 60);
    }

    // Relationship with the Company model (if you have one)
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function companyLocation()
    {
        return $this->belongsTo(CompanyLocation::class);
    }

    public function arrivalLocation()
    {
        return $this->belongsTo(ArrivalLocation::class);
    }
}
