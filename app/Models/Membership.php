<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class Membership extends Pivot
{
    //
    protected $table = 'memberships';

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

}
