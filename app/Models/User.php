<?php

namespace App\Models;

use App\Models\Acl\Company;
use App\Models\Acl\LoginHistory;
use App\Models\Master\Account\Account;
use App\Models\Master\Account\Transaction;
use App\Models\Master\ArrivalLocation;
use App\Models\Master\CompanyLocation;
use BaconQrCode\Common\ErrorCorrectionLevel;
use BaconQrCode\Encoder\QrCode;
use BaconQrCode\Renderer\ImageRenderer;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, HasRoles, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'user_type',
        'parent_user_id',
        'status',
        'current_company_id',
        'company_location_id',
        'arrival_location_id',
        // json
        'company_location_ids',
        'arrival_location_ids',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array
     */
    protected $hidden = ['password', 'remember_token'];

    public function companies()
    {
        return $this->belongsToMany(Company::class, 'company_user_role')
            ->withPivot('role_id', 'locations', 'arrival_locations')
            ->withTimestamps();
    }

    public function companyLocation()
    {
        return $this->belongsTo(CompanyLocation::class, 'company_location_id');
    }

    public function arrivalLocation()
    {
        return $this->belongsTo(ArrivalLocation::class, 'arrival_location_id');
    }

    public function currentCompany()
    {
        return $this->hasOne(Company::class, 'id', 'current_company_id');
    }

    public function loginHistories()
    {
        return $this->hasMany(LoginHistory::class, 'user_id')->orderBy('id', 'desc');
    }

    public function getAuthIdentifierName()
    {
        return 'username';
    }

    public function parent()
    {
        return $this->belongsTo(User::class, 'parent_user_id');
    }

    public function children()
    {
        return $this->hasMany(User::class, 'parent_user_id');
    }

    public function getAuthIdentifier()
    {
        return $this->username;
    }

    public static function loginHistory()
    {
        return User::where('id', Auth::user()->id)
            ->with([
                'loginHistories' => function ($query) {
                    $query->orderBy('created_at', 'desc')->limit(10); // Limit to 10 login history records
                },
            ])
            ->first();
    }

    public function resetTwoFactorAuthenticationCode()
    {
        $newCode = $this->twoFactorCode();
        $this->update(['google2fa_secret' => '']);

        return $newCode;
    }

    private function generateQRCodeUrl($issuer, $email, $secret)
    {
        $url = sprintf('otpauth://totp/%s:%s?secret=%s&issuer=%s', rawurlencode($issuer), rawurlencode($email), $secret, rawurlencode($issuer));

        $renderer = new ImageRenderer(new \BaconQrCode\Renderer\RendererStyle\RendererStyle(400), new DefaultImageWriter);

        $generator = new \BaconQrCode\Generator\Generator($renderer);
        $qrCode = $generator->generate($url, new QrCode, new ErrorCorrectionLevel(ErrorCorrectionLevel::MEDIUM));

        ob_start();
        imagepng($qrCode->toImage());
        $qrCodeImage = ob_get_contents();
        ob_end_clean();

        return 'data:image/png;base64,'.base64_encode($qrCodeImage);
    }

    public function leadsAssignedTo()
    {
        return $this->belongsToMany(Lead::class, 'lead_assignee_bridges', 'assign_to', 'lead_id');
    }

    public function leadsWatching()
    {
        return $this->belongsToMany(Lead::class, 'lead_assignee_bridges', 'watcher', 'lead_id');
    }

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'company_location_ids' => 'array',
        'arrival_location_ids' => 'array',
    ];

    public function createdAccounts()
    {
        return $this->hasMany(Account::class, 'created_by');
    }

    public function updatedAccounts()
    {
        return $this->hasMany(Account::class, 'updated_by');
    }

    public function createdTransactions()
    {
        return $this->hasMany(Transaction::class, 'created_by');
    }

    public function updatedTransactions()
    {
        return $this->hasMany(Transaction::class, 'updated_by');
    }
}
