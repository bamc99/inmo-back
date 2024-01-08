<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdditionalApplicationStage extends Model
{
    use HasFactory;

    protected $fillable = [
        'loan_application_id',
        'stage',
        'data'
    ];

    protected $appends = [
        'data'
    ];

    public function getDataAttribute()
    {
        return json_decode($this->attributes['data']);
    }

    public function loanApplication()
    {
        return $this->belongsTo(LoanApplication::class);
    }

}
