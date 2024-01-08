<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Role extends \Spatie\Permission\Models\Role
{
    protected $fillable = ['name', 'guard_name'];
    protected $hidden = ['guard_name', 'pivot'];
}
