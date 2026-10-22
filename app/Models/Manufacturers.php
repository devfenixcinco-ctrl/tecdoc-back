<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Manufacturers extends Model
{
    protected $table = 'manufacturers';

    protected $primaryKey = 'id';

    protected $fillable = [
        'id',
        'name',
        'image',
        'linkingType',
    ];
}
