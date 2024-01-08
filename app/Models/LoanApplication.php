<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

// Solicitudes Prestamos
class LoanApplication extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'quotation_id',
        'bank_id',
        'amortization_data',
        'current_stage',
        'start_application_id',
        'end_application_id',
        'confirm_application',
        'start_attached_id',
        'end_attached_id',
        'confirm_attached',
        'signature_date',
        'confirm_conditions',
        'is_active',
    ];

    protected $appends = [
        'amortization_data',
    ];

    public function getAmortizationDataAttribute()
    {

        $montosData = collect(json_decode($this->attributes['amortization_data']));
        unset($montosData['amortizacion']);
        unset($montosData['dataTir']);
        unset($montosData['arrayTir']);

        return $montosData;
    }


    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function quotation()
    {
        return $this->belongsTo(Quotation::class);
    }

    public function bank()
    {
        return $this->belongsTo(Bank::class);
    }

    // Attachment
    public function startApplication (){
        return $this->belongsTo(Attachment::class, 'start_application_id');
    }

    public function endApplication (){
        return $this->belongsTo(Attachment::class, 'end_application_id');
    }

    public function startAttached (){
        return $this->belongsTo(Attachment::class, 'start_attached_id');
    }

    public function endAttached (){
        return $this->belongsTo(Attachment::class, 'end_attached_id');
    }

    public function loanApplicationAttachments()
    {
        return $this->hasMany(LoanApplicationAttachments::class);
    }

    public function additionalApplicationStage()
    {
        return $this->hasMany(AdditionalApplicationStage::class);
    }

}
