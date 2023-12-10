<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MacdbModel extends Model
{
    use HasFactory;
    protected $table = 'macdb';
    protected $primaryKey = 'id';
}
