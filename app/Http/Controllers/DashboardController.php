<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Razorpay\Api\Api;
use Validator;

class DashboardController extends Controller
{
    private function generate_p_id()
    {
        $uniqueNo = 'BSBN';
        $uniqueCode = mt_rand(10000, 99999);
        return $uniqueNo . $uniqueCode;
    }

    public function index()
    {
        return view('web.dashboard');
    }

    public function renewal()
    {
        $planbyop = $this->planbyop();
        $subplanbyop = DB::table('tbl_subplan')->where('pkg_id', Auth::user()->plan_id)->get();
        if (!empty(Auth::user()->usersubplan)) {
            $subplanprice = DB::table('tbl_subplan')->where('subplan_id', Auth::user()->usersubplan->subplan_id)->first();
        } else {
            $subplanprice = array();
        }
        $pgdetails = DB::table('tbl_pg')->where('pg_id', 1)->first();
        return view('web.renewal', compact('planbyop', 'subplanbyop', 'subplanprice', 'subplanprice'));
    }

    public function planbyop()
    {
        // $pid = $this->session->userdata('partnerid');
        // $ptype = ptype($pid);
        $ptype = ptype(Auth::user()->partner_id);

        // $this->db->select('*');

        if ($ptype == 0) {
            // $query = $this->db->get('tbl_plan');
            $query = DB::table('tbl_plan')->get();
            return $query;
        } else {
            // $this->db->where('plan_id IN (SELECT plan_id FROM tbl_sharing WHERE user_id=' . $pid . ')', null, false);
            // $query = $this->db->get('tbl_plan');

            $planIds = DB::table('tbl_sharing')->select('plan_id')->where('user_id', Auth::user()->partner_id)->get()->toArray();
            $query = DB::table('tbl_plan')->whereIn('plan_id', $planIds)->get();
            return $query;
        }
        // if ($query->num_rows() > 0) {
        //     return $query->result();
        // } else {
        //     return false;
        // }
    }

    public function subPlanByPlanId(Request $request)
    {
        $plan_id = $request->plan_id;
        if (isset($plan_id) && $plan_id != "") {
            // $query = $this->db->where("pkg_id", $plan_id)->get("tbl_subplan");
            $query = DB::table('tbl_subplan')->where("pkg_id", $plan_id)->get();
            if ($query) {
                return response()->json($query);
            } else {
                return response()->json(array());
            }
        } else {
            return response()->json(array());
        }
    }

    public function razorpaypgCheckout(Request $request)
    {
        $validator  = Validator::make($request->all(), [
            'partner_id'    => 'required',
            'id'            => 'required',
            'mobile'        => 'required',
            'email'         => 'required',
            'plan_id'       => 'required',
            'subplan_id'    => 'required',
            'name'          => 'required'
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->messages() as $key => $error) {
                flash()->addError($error[0]);
            }
            return redirect()->back();
        } else {
            $odid =  $this->generate_p_id();
            // // $query = $this->db->select('Razorpay_merchentkey AS pkey,Razorpay_secret AS secret')->get('tbl_pg')->row();
            $query = DB::table('tbl_pg')->select('Razorpay_merchentkey AS pkey', 'Razorpay_secret AS secret')->where('pg_id', 1)->first();

            // if (is_null($query->pkey) || is_null($query->secret)) {
            //     flash()->addError('Please check payment gateway settings');
            //     return redirect()->back();
            // }

            // dd($request->all());

            $key_id = "rzp_test_kyJdC0HmEWR67G";
            $secret_key = "6pkM5WJZRyoWdjpa3JiSDWIL";

            // $key_id = $query->pkey;
            // $secret_key = $query->secret;


            $subplan_id = $request->subplan_id;
            // // $amount = (int) $this->db->where('subplan_id', $subplan_id)->get('tbl_subplan')->row()->total_price;
            $amount = DB::table('tbl_subplan')->where('subplan_id', $subplan_id)->first('total_price');
            $amount = (int)$amount->total_price * 100;

            $api = new Api($key_id, $secret_key);
            $order = $api->order->create(array(
                'receipt' => $odid,
                'amount' =>  $amount,
                'currency' => 'INR',
                'payment_capture' => 1
            ));

            // // $advcheck = (isset($_POST['advancedrenew'])) ? '1' : '0';
            $advcheck = $request->has('advancedrenew') ? 1 : 0;

            $dataofcustomer = array(
                'partnerid' => $request->partner_id,
                'id' => $order->id,
                'custid' => $request->id,
                'custname' => $request->name,
                'mobile' => $request->mobile,
                'email' => $request->email,
                'subplan_id' => $subplan_id,
                'advcheck' => $advcheck
            );
            // $this->session->set_userdata('privatedetails', $dataofcustomer);

            session()->push('privatedetails', $dataofcustomer);
            // dd(session()->get('test_session'));

            // $this->load->view('razorpaycheckout', ['key' => $key_id, 'order' => $order, 'dataofcust' => $dataofcustomer]);
            return view('web.razorpaycheckout', compact('order', 'dataofcust'))->with('key', $key_id);
        }
    }

    public function getSubplanbyIdfordata(Request $request)
    {
        $subplan_id = $request->subplan_id;
        if (isset($subplan_id) && $subplan_id != "") {
            $query = DB::table('tbl_subplan')->where("subplan_id", $subplan_id)->first();
            if ($query) {
                return response()->json($query);
            } else {
                return response()->json(array());
            }
        } else {
            return response()->json(array());
        }
    }

    public function sessionHistory()
    {
        $user_id = Auth::user()->id;
        $sessionhistory = DB::select(DB::raw(" SELECT * FROM `radacct` WHERE username IN (SELECT username FROM tbl_customers WHERE id = '$user_id') OR username IN (SELECT macaddress FROM tbl_customers WHERE id = '$user_id') ORDER BY `radacctid` DESC "));
        return view('web.session_history', compact('sessionhistory'));
    }


    // public function getsessionhistory()
    // {
    //     $user_id = $this->session->userdata('user_id');
    //     $query = $this->db->query(" SELECT * FROM `radacct` WHERE 
    //     username IN (SELECT username FROM tbl_customers WHERE id = '$user_id')
    //     OR username IN (SELECT macaddress FROM tbl_customers WHERE id = '$user_id')
    //      ORDER BY `radacctid` DESC ");
    //     if ($query->num_rows() > 0) {
    //         return $query->result();
    //     } else {
    //         return false;
    //     }
    // }

    public function authLogs()
    {
        $user_id = Auth::user()->id;
        $authlogs = DB::select(DB::raw("SELECT * FROM `radpostauth` WHERE username IN (SELECT username FROM tbl_customers WHERE id = '$user_id') OR username IN (SELECT macaddress FROM tbl_customers WHERE id = '$user_id') ORDER BY radpostauth.id DESC LIMIT 200 "));

        return view('web.authlogs', compact('authlogs'));
    }

    // public function getauthlogs()
    // {
    //     $user_id = $this->session->userdata('user_id');
    //     $query = $this->db->query(" SELECT * FROM `radpostauth` WHERE 
    //     username IN (SELECT username FROM tbl_customers WHERE id = '$user_id')
    //     OR
    //     username IN (SELECT macaddress FROM tbl_customers WHERE id = '$user_id')
    //      ORDER BY radpostauth.id DESC LIMIT 200 ");
    //     if ($query->num_rows() > 0) {
    //         return $query->result();
    //     } else {
    //         return false;
    //     }
    // }

    public function showInvoices()
    {
        $user_id = Auth::user()->id;

        $showinvoices = DB::table('tbl_invoices')
            ->leftJoin('tbl_plan', 'tbl_invoices.plan_id', '=', 'tbl_plan.plan_id')
            ->leftJoin('tbl_subplan', 'tbl_invoices.sub_plan_id', '=', 'tbl_subplan.subplan_id')
            ->where('customer_id', $user_id)
            ->select('tbl_invoices.*', 'tbl_plan.plan_name as planname', 'tbl_subplan.name as subplanname')
            ->orderBy('invoice_id', 'DESC')
            ->get();

        return view('web.showinvoices', compact('showinvoices'));
    }

    // public function getallinvoices()
    // {
    //     $user_id = $this->session->userdata('user_id');
    //     $query = $this->db->select('tbl_invoices.*, tbl_plan.plan_name as planname,tbl_subplan.name as subplanname')
    //     ->from('tbl_invoices')
    //     ->join('tbl_plan', 'tbl_invoices.plan_id = tbl_plan.plan_id', 'left')
    //     ->join('tbl_subplan', 'tbl_invoices.sub_plan_id = tbl_subplan.subplan_id', 'left')
    //     ->where('customer_id', $user_id)
    //         ->order_by('invoice_id', 'DESC')
    //         ->get();

    //     if ($query->num_rows() > 0) {
    //         return $query->result();
    //     } else {
    //         return false;
    //     }
    // }

    public function complaint()
    {
        $templates = DB::table('tbl_complainttype')->where('parent_id', Auth::user()->partner_id)->get();
        $complaints = DB::table('tbl_complaints')
            ->leftJoin('tbl_admins', 'tbl_admins.id', '=', 'tbl_complaints.assigned_id')
            ->leftJoin('tbl_complainttype', 'tbl_complainttype.complainttype_id', '=', 'tbl_complaints.complainttype_id')
            ->where('tbl_complaints.customer_id', Auth::user()->id)
            ->select('tbl_complaints.*', 'tbl_admins.name AS assigned_name', 'tbl_complainttype.complaint_type')
            ->orderBy('tbl_complaints.complaint_id', 'DESC')
            ->get();
        return view('web.complaint', compact('templates', 'complaints'));
    }

    // public function complaintsbyid()
    // {
    //     $user_id = $this->session->userdata('user_id');

    //     $query = $this->db->select('tbl_complaints.*, tbl_admins.name AS assigned_name, tbl_complainttype.complaint_type')
    //         ->from('tbl_complaints')
    //         ->join('tbl_admins', 'tbl_admins.id = tbl_complaints.assigned_id', 'left')
    //         ->join('tbl_complainttype', 'tbl_complainttype.complainttype_id = tbl_complaints.complainttype_id', 'left')
    //         ->where('tbl_complaints.customer_id', $user_id)
    //         ->order_by('tbl_complaints.complaint_id', 'DESC')
    //         ->get();

    //     if ($query->num_rows() > 0) {
    //         return $query->result();
    //     } else {
    //         return false;
    //     }
    // }

    public function createcomplaint(Request $request)
    {
        $validator  = Validator::make($request->all(), [
            'template_id'           => 'required',
            'complaintscomments'    => 'required',
            'priority'              => 'required|in:0,1,2,3',
        ]);
        if ($validator->fails()) {
            flash()->addError('All fields are Required');
            return redirect()->back();
        } else {
            DB::table('tbl_complaints')->insert([
                'parent_id' => Auth::user()->partner_id,
                'priority' => $request->priority,
                'customer_id' => Auth::user()->id,
                'complainttype_id' =>  $request->template_id,
                'message' => $request->complaintscomments,
            ]);
            flash()->addSuccess('Complain Submitted Successfully.');
            return redirect()->back();
        }
    }
}
