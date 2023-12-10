<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class User extends Authenticatable
{
    protected $table = 'tbl_customers';

    use HasFactory, Notifiable;
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'username', 'password',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function userPlan()
    {
        return $this->belongsTo(PlanModel::class, 'plan_id');
    }

    public function userSubplan()
    {
        return $this->belongsTo(SubPlanModel::class, 'subplan_id');
    }

    public function alldetailsfordashboard()
    {

        $query = DB::table('tbl_customers')
            ->leftJoin('tbl_subplan', 'tbl_subplan.subplan_id', '=', 'tbl_customers.subplan_id')
            ->leftJoin('tbl_plan', 'tbl_plan.plan_id', '=', 'tbl_customers.plan_id')
            ->where('tbl_customers.id', Auth::user()->id)
            ->select('tbl_customers.*', 'tbl_plan.plan_name as planname', 'tbl_plan.plan_type as plan_type', 'tbl_subplan.name as subplanname', 'tbl_subplan.duration_type as duration_type', 'tbl_subplan.duration as duration', 'tbl_subplan.price as price')
            ->get();
        // dd($query);
        // $this->db->select('tbl_customers.*, tbl_plan.plan_name as planname, tbl_plan.plan_type as plan_type, tbl_subplan.name as subplanname, tbl_subplan.duration_type as duration_type, tbl_subplan.duration as duration, tbl_subplan.price as price')
        // ->from('tbl_customers')
        // ->join('tbl_subplan', 'tbl_subplan.subplan_id = tbl_customers.subplan_id', 'left')
        // ->join('tbl_plan', 'tbl_plan.plan_id = tbl_customers.plan_id', 'left')

        // ->where('tbl_customers.id', $user_id); // Corrected the where condition

        // $query = $this->db->get();

        // if ($query->num_rows() > 0) {
        //     return $query->row();
        // } else {
        //     return false;
        // }
    }
}
