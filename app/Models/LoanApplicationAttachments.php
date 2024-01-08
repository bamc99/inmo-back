<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\Pivot;

class LoanApplicationAttachments extends Pivot
{

    protected $fillable = [
        'loan_application_id',
        'attachment_id',
        'type',
    ];

    public function loanApplication()
    {
        return $this->belongsTo(LoanApplication::class);
    }

    public function attachment()
    {
        return $this->belongsTo(Attachment::class);
    }
}
