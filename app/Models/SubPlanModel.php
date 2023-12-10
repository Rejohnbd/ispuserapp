<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubPlanModel extends Model
{
    use HasFactory;
    protected $table = 'tbl_subplan';
    protected $primaryKey = 'subplan_id';
}
