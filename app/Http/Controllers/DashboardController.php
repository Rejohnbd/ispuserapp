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
        $result = DB::table('tbl_subscriberportalsettings')->where('settings_id', 1)->first('is_sub_dash');
        $status = $result->is_sub_dash;
        return view('web.dashboard', compact('status'));
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
            $planIds = DB::table('tbl_sharing')->select('plan_id')->where('user_id', Auth::user()->partner_id)->get()->pluck('plan_id')->toArray();
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
            $query = DB::table('tbl_pg')->select('Razorpay_merchentkey AS pkey', 'Razorpay_secret AS secret')->where('pg_id', 1)->first();

            if (is_null($query->pkey) || is_null($query->secret)) {
                flash()->addError('Please check payment gateway settings');
                return redirect()->back();
            }

            // $key_id = "rzp_test_bhGDawmQOSmngt";
            // $secret_key = "ApqgvYfN8HugTlmrgklu3JAo";

            $key_id = $query->pkey;
            $secret_key = $query->secret;

            $subplan_id = $request->subplan_id;

            $amount = DB::table('tbl_subplan')->where('subplan_id', $subplan_id)->first('total_price');
            $amount = (int)$amount->total_price * 100;

            $api = new Api($key_id, $secret_key);
            $order = $api->order->create(array(
                'receipt' => $odid,
                'amount' =>  $amount,
                'currency' => 'INR',
                'payment_capture' => 1
            ));

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

            session()->put('privatedetails', $dataofcustomer);

            return view('web.razorpaycheckout')->with([
                'key' => $key_id,
                'dataofcust' => $dataofcustomer,
                'order' => $order
            ]);
        }
    }

    public function razorpaypgStatus(Request $request)
    {
        if ($_POST) {
            $razorpay_signature = $_POST['razorpay_signature'];
            $order_id = $_POST['razorpay_order_id'];
            $payment_id = $_POST['razorpay_payment_id'];

            // Verify the Razorpay signature (you should have a method or library to do this)
            if ($this->verifyRazorpaySignature($razorpay_signature, $order_id, $payment_id)) {

                $dataofcustomer = session()->get('privatedetails');

                $advcheck = $dataofcustomer['advcheck'];


                $res_data = array(
                    'customer_id' => $dataofcustomer['custid'],
                    'partner_id' => $dataofcustomer['partnerid'],
                    'razorpay_payment_id' => $payment_id,
                    'razorpay_order_id' => $order_id,
                    'razorpay_signature' => $razorpay_signature
                );

                // $this->db->insert('tbl_payment_gateway_response', $res_data);
                DB::table('tbl_payment_gateway_response')->insert($res_data);


                // //RENEW
                // $customer_data = $this->db->where('id', $dataofcustomer['custid'])->get('tbl_customers')->row();
                $customer_data = DB::table('tbl_customers')->where('id', $dataofcustomer['custid'])->first();
                $subplan_id = $dataofcustomer['subplan_id'];
                // $subplan_data = $this->db->where('subplan_id', $dataofcustomer['subplan_id'])->get('tbl_subplan')->row();
                $subplan_data = DB::table('tbl_subplan')->where('subplan_id', $dataofcustomer['subplan_id'])->first();
                $plan_id = $subplan_data->pkg_id;
                // $plan_data = $this->db->where('plan_id', $plan_id)->get('tbl_plan')->row();
                $plan_data = DB::table('tbl_plan')->where('plan_id', $plan_id)->first();
                $fpartnerid = $dataofcustomer['partnerid'];

                $subplan_tax_status = $subplan_data->tax_status;
                if ($subplan_tax_status == '0') {
                    $subplan_total_amount = $subplan_data->total_price;
                } else {
                    $subplan_total_amount = (float)$subplan_data->price;
                }
                $subplan_tax_price = $subplan_data->tax_price;
                //THIS CUSTOMER OWNER


                // $my_parent_id = $this->db->where('id', $fpartnerid)->get('tbl_admins')->row()->parent_id;
                $myParentId = DB::table('tbl_admins')->where('id', $fpartnerid)->first('parent_id');
                $my_parent_id = $myParentId->parent_id;
                // $advancedrenewbtn = post('advancedrenew');
                $upcoming_date = "";
                $renewMessage = "";
                if (!empty($advcheck) && $advcheck == 1) {
                    $upcoming_date = calculate_upcoming_datetime($subplan_data->duration_type, $subplan_data->duration, $plan_data->expiry_type, strtotime($customer_data->expired_at));
                    $renewMessage = "Advance Renew Successfull";
                } else {
                    $upcoming_date = calculate_upcoming_datetime($subplan_data->duration_type, $subplan_data->duration, $plan_data->expiry_type);
                    $renewMessage = "Renew Successfull";
                }

                $normal_expiry = date('Y-m-d H:i:s', strtotime($upcoming_date));
                // dd($normal_expiry);

                $utype = ptype($fpartnerid);

                if ($utype == 0) { //SUPERADMIN-ISP
                    //
                    // $upcoming_date = calculate_upcoming_datetime($subplan_data->duration_type, $subplan_data->duration, $plan_data->expiry_type);
                    // $normal_expiry = date('Y-m-d H:i:s', strtotime($upcoming_date));
                    $base_price = (float)$subplan_data->price;

                    if ($base_price) {

                        // $this->db->where('username', $customer_data->username)->set('value', $upcoming_date)->where('attribute', 'Expiration')->update('radcheck');
                        DB::table('radcheck')->where('username', $customer_data->username)->where('attribute', 'Expiration')->update(['value' => $upcoming_date]);
                        // $this->db->where('id', $customer_data->id)->set('expired_at', $normal_expiry)->set('renew_at', date('Y-m-d H:i:s'))->update('tbl_customers');
                        DB::table('tbl_customers')->where('id', $customer_data->id)->update(['expired_at' => $normal_expiry, 'renew_at' => date('Y-m-d H:i:s')]);
                        // $duedays = (int) $this->db->where('settings_id', '1')->get('tbl_generalsettings')->row()->dueindays;
                        $dueDays = DB::table('tbl_generalsettings')->where('settings_id', '1')->first('dueindays');
                        $duedays = (int) $dueDays->dueindays;

                        if ($duedays) {
                            $newDateDues = date('Y-m-d', strtotime(date('Y-m-d') . ' + ' . $duedays . ' days'));
                        } else {
                            $newDateDues = date('Y-m-d', strtotime(date('Y-m-d') . ' + 2 days'));
                        }
                        $data = array(
                            'invoice_code' => generate_customer_invoice_code(),
                            'customer_id' => $customer_data->id,
                            'parent_id' => $fpartnerid,
                            'plan_id' => $plan_id,
                            'sub_plan_id' => $subplan_id,
                            'last_expiry_date' => date('Y-m-d H:i:s'),
                            'upcoming_expiry_date' => $normal_expiry,
                            'due_on' => $newDateDues,
                            'base_price' => $base_price,
                            'tax_amount' => $subplan_tax_price,
                            'invoice_status' => '0',
                            'camount_paid' => $subplan_total_amount,
                            'total_amount' => $subplan_total_amount,
                            'partner_amount_dedected' => 0,
                            'partner_earning_amount' => 0,
                            'payment_mode' => '1'
                        );
                        // $this->db->insert('tbl_invoices', $data);
                        DB::table('tbl_invoices')->insert($data);
                        // $this->db->where('id', $dataofcustomer['custid'])->update('tbl_customers', ['plan_id' => $plan_id, 'subplan_id' => $subplan_id]);
                        DB::table('tbl_customers')->where('id', $dataofcustomer['custid'])->update(['plan_id' => $plan_id, 'subplan_id' => $subplan_id]);
                        // $this->db->where('username', $customer_data->username)->update('radusergroup', ['groupname' => $plan_data->plan_code]);
                        DB::table('radusergroup')->where('username', $customer_data->username)->update(['groupname' => $plan_data->plan_code]);

                        if ($advcheck == 0) {
                            disonnectusers($customer_data->username);
                        }

                        // $this->session->set_flashdata('success', $renewMessage);
                        // accesslog($this->session->flashdata('success'));
                        // redirect('/user/showinvoices');

                        flash()->addSuccess($renewMessage);
                        accesslog($renewMessage);
                        return redirect()->route('showinvoices');
                    } else {
                        // $this->session->set_flashdata('error', 'You do not have enough amount in your wallet');
                        // accesslog($this->session->flashdata('error'));
                        // redirect('customers/editcustomer/' . base64_encode($rcustomerid));
                        //No Customer Access
                        flash()->addSuccess('error');
                        accesslog('error');
                        return redirect()->back();
                    }
                    //

                } else if ($utype == 1) { //FRANCHISE-LCO
                    //
                    // $my_wallet_balance = (int) $this->db->where('u_id', $fpartnerid)->get('tbl_wallet')->row()->wallet_balance;
                    $myWalletBalance = DB::table('tbl_wallet')->where('u_id', $fpartnerid)->first('wallet_balance');
                    $my_wallet_balance = (int) $myWalletBalance->wallet_balance;

                    $base_price = (float)$subplan_data->price;

                    // $my_commision = $this->db->where('plan_id', $plan_id)->where('user_id', $fpartnerid)->where('subplan_id', $subplan_id)->get('tbl_sharing')->row()->com_value;
                    $my_commision = DB::table('tbl_sharing')->where('plan_id', $plan_id)->where('user_id', $fpartnerid)->where('subplan_id', $subplan_id)->first('com_value');
                    $my_commision_value = round($base_price * $my_commision->com_value / 100);

                    // dd($my_commision_value);
                    // $my_deduction = round($base_price - $my_commision_value + $subplan_tax_price);

                    // wallet_transactions for me
                    //     $this->db->insert('wallet_transactions', [
                    //         'u_id' => $fpartnerid,
                    //         'amount_history' => $my_commision_value,
                    //         'preveous_amount' => $my_wallet_balance,
                    //         'action' => '0',
                    //         'payment_type' => '1',
                    //         'comments' => "Renew of $customer_data->username, credited amount $my_commision_value"
                    //     ]);

                    DB::table('wallet_transactions')->insert([
                        'u_id' => $fpartnerid,
                        'amount_history' => $my_commision_value,
                        'preveous_amount' => $my_wallet_balance,
                        'action' => '0',
                        'payment_type' => '1',
                        'comments' => "Renew of $customer_data->username, credited amount $my_commision_value"
                    ]);

                    // $this->db->where('u_id', $fpartnerid)->set('wallet_balance', 'wallet_balance + ' . $my_commision_value, FALSE)->update('tbl_wallet');
                    DB::table('tbl_wallet')->where('u_id', $fpartnerid)->update(['wallet_balance' => DB::raw('wallet_balance + ' . $my_commision_value)]);
                    // $this->db->where('username', $customer_data->username)->set('value', $upcoming_date)->where('attribute', 'Expiration')->update('radcheck');
                    DB::table('radcheck')->where('username', $customer_data->username)->where('attribute', 'Expiration')->update(['value' => $upcoming_date]);
                    // $this->db->where('id', $dataofcustomer['custid'])->set('expired_at', $normal_expiry)->set('renew_at', date('Y-m-d H:i:s'))->update('tbl_customers');
                    DB::table('tbl_customers')->where('id', $dataofcustomer['custid'])->update(['expired_at' => $normal_expiry, 'renew_at' => date('Y-m-d H:i:s')]);
                    // $duedays = (int) $this->db->where('settings_id', '1')->get('tbl_generalsettings')->row()->dueindays;
                    $dueDays = DB::table('tbl_generalsettings')->where('settings_id', '1')->first('dueindays');
                    $duedays = (int)$dueDays->dueindays;

                    if ($duedays) {
                        $newDateDues = date('Y-m-d', strtotime(date('Y-m-d') . ' + ' . $duedays . ' days'));
                    } else {
                        $newDateDues = date('Y-m-d', strtotime(date('Y-m-d') . ' + 2 days'));
                    }
                    $data = array(
                        'invoice_code' => generate_customer_invoice_code(),
                        'customer_id' => $dataofcustomer['custid'],
                        'parent_id' => $fpartnerid,
                        'plan_id' => $plan_id,
                        'sub_plan_id' => $subplan_id,
                        'last_expiry_date' => date('Y-m-d H:i:s'),
                        'upcoming_expiry_date' => $normal_expiry,
                        'due_on' => $newDateDues,
                        'base_price' => $base_price,
                        'tax_amount' => $subplan_tax_price,
                        'invoice_status' => '0',
                        'camount_paid' => $subplan_data->total_price,
                        'total_amount' => $subplan_total_amount,
                        'partner_amount_dedected' => 0,
                        'partner_earning_amount' => $my_commision_value,
                        'payment_mode' => '1'
                    );
                    // $this->db->insert('tbl_invoices', $data);
                    DB::table('tbl_invoices')->insert($data);
                    // $this->db->where('id', $dataofcustomer['custid'])->update('tbl_customers', ['plan_id' => $plan_id, 'subplan_id' => $subplan_id]);
                    DB::table('tbl_customers')->where('id', $dataofcustomer['custid'])->update(['plan_id' => $plan_id, 'subplan_id' => $subplan_id]);
                    // $this->db->where('username', $customer_data->username)->update('radusergroup', ['groupname' => $plan_data->plan_code]);
                    DB::table('radusergroup')->where('username', $customer_data->username)->update(['groupname' => $plan_data->plan_code]);


                    disonnectusers($customer_data->username);
                    //     $this->session->set_flashdata('success', $renewMessage);
                    //     accesslog($this->session->flashdata('success'));
                    //     redirect('/user/showinvoices');
                    flash()->addSuccess($renewMessage);
                    accesslog($renewMessage);
                    return redirect()->route('showinvoices');

                    //     //
                } else if ($utype == 2) { //SUB FRANCHISE-SUB LCO
                    // $my_wallet_balance = (int) $this->db->where('u_id', $fpartnerid)->get('tbl_wallet')->row()->wallet_balance;
                    $myWalletBalance = DB::table('tbl_wallet')->where('u_id', $fpartnerid)->first('wallet_balance');
                    $my_wallet_balance = (int) $myWalletBalance->wallet_balance;
                    // $parent_wallet_balance = (int) $this->db->where('u_id', $my_parent_id)->get('tbl_wallet')->row()->wallet_balance;
                    $parentWalletBalance = DB::table('tbl_wallet')->where('u_id', $my_parent_id)->first('wallet_balance');
                    $parent_wallet_balance = (int) $parentWalletBalance->wallet_balance;
                    $upcoming_date = calculate_upcoming_datetime($subplan_data->duration_type, $subplan_data->duration, $plan_data->expiry_type);
                    // $normal_expiry = date('Y-m-d H:i:s', strtotime($upcoming_date));
                    $base_price = (float)$subplan_data->price;
                    // $my_commision = $this->db->where('plan_id', $plan_id)->where('user_id', $fpartnerid)->where('subplan_id', $subplan_id)->get('tbl_sharing')->row()->com_value;
                    $my_commision = DB::table('tbl_sharing')->where('plan_id', $plan_id)->where('user_id', $fpartnerid)->where('subplan_id', $subplan_id)->first('com_value');
                    // $parent_commision = $this->db->query(" SELECT com_value FROM `tbl_sharing` WHERE plan_id='$plan_id' AND subplan_id='$subplan_id' AND user_id IN (SELECT parent_id FROM tbl_admins WHERE id='$fpartnerid') ")->row()->com_value;
                    $parent_commision = DB::select(DB::raw(" SELECT com_value FROM `tbl_sharing` WHERE plan_id='$plan_id' AND subplan_id='$subplan_id' AND user_id IN (SELECT parent_id FROM tbl_admins WHERE id='$fpartnerid')"));
                    // echo '$parent_commision: '.$parent_commision.'</br>';

                    $my_commision_value = round($base_price * $my_commision->com_value / 100);
                    // //                    echo '$my_commision_value: '.$my_commision_value.'</br>';
                    // $parent_commision_value = round($base_price * $parent_commision / 100);
                    $parent_commision_value = round($base_price * $parent_commision['com_value'] / 100);
                    // echo 'parent_commision_value: ' . $parent_commision_value . '</br>';
                    // $my_deduction = round($base_price - $my_commision_value + $subplan_tax_price);
                    // echo '$my_deduction:'.$my_deduction.'</br>';
                    $ruturned_commision_value = ($parent_commision['com_value'] - $my_commision->com_value);
                    // $ruturned_commision_value = ($parent_commision - $my_commision);
                    $ruturned_commision_value = ($parent_commision['com_value']  - $my_commision->com_value);
                    // // echo '$ruturned_commision_value:'.$ruturned_commision_value.'</br>';
                    $returned_amount = round($base_price * $ruturned_commision_value / 100);
                    //  echo '$returned_amount:'.$returned_amount.'</br>';

                    //				wallet_transactions for me
                    //     $this->db->insert('wallet_transactions', [
                    //         'u_id' => $fpartnerid,
                    //         'amount_history' => $my_commision_value,
                    //         'preveous_amount' => $my_wallet_balance,
                    //         'action' => '0',
                    //         'payment_type' => '1',
                    //         'comments' => "Renew of $customer_data->username, credited amount $my_commision_value"
                    //     ]);
                    DB::table('wallet_transactions')->insert([
                        'u_id' => $fpartnerid,
                        'amount_history' => $my_commision_value,
                        'preveous_amount' => $my_wallet_balance,
                        'action' => '0',
                        'payment_type' => '1',
                        'comments' => "Renew of $customer_data->username, credited amount $my_commision_value"
                    ]);
                    //     //                        $this->db->where('u_id', $fpartnerid)->set('wallet_balance', $mmy_wallet_balance)->update('tbl_wallet');
                    //     $this->db->where('u_id', $fpartnerid)->set('wallet_balance', 'wallet_balance + ' . $my_commision_value, FALSE)->update('tbl_wallet');

                    DB::table('tbl_wallet')->where('u_id', $fpartnerid)->update(['wallet_balance' => DB::raw('wallet_balance + ' . $my_commision_value)]);
                    // $this->db->insert('wallet_transactions', [
                    //     'u_id' => $my_parent_id,
                    //     'amount_history' => $returned_amount,
                    //     'preveous_amount' => $parent_wallet_balance,
                    //     'action' => '0',
                    //     'payment_type' => '1',
                    //     'comments' => "Renew of $customer_data->username, credited amount $returned_amount"
                    // ]);
                    DB::table('wallet_transactions')->insert([
                        'u_id' => $my_parent_id,
                        'amount_history' => $returned_amount,
                        'preveous_amount' => $parent_wallet_balance,
                        'action' => '0',
                        'payment_type' => '1',
                        'comments' => "Renew of $customer_data->username, credited amount $returned_amount"
                    ]);
                    //     $this->db->where('u_id', $my_parent_id)->set('wallet_balance', 'wallet_balance + ' . $returned_amount, FALSE)->update('tbl_wallet');
                    DB::table('tbl_wallet')->where('u_id', $my_parent_id)->update(['wallet_balance' => DB::raw('wallet_balance + ' . $returned_amount)]);
                    //     $this->db->where('username', $customer_data->username)->set('value', $upcoming_date)->where('attribute', 'Expiration')->update('radcheck');
                    DB::table('radcheck')->where('username', $customer_data->username)->where('attribute', 'Expiration')->update(['value' => $upcoming_date]);
                    //     $this->db->where('id', $customer_data->id)->set('expired_at', $normal_expiry)->set('renew_at', date('Y-m-d H:i:s'))->update('tbl_customers');
                    DB::table('tbl_customers')->where('id', $customer_data->id)->update(['expired_at' => $normal_expiry, 'renew_at' => date('Y-m-d H:i:s')]);
                    //     $duedays = (int) $this->db->where('settings_id', '1')->get('tbl_generalsettings')->row()->dueindays;
                    $dueDays = DB::table('tbl_generalsettings')->where('settings_id', '1')->first('dueindays');
                    $duedays = (int) $dueDays->dueindays;
                    if ($duedays) {
                        $newDateDues = date('Y-m-d', strtotime(date('Y-m-d') . ' + ' . $duedays . ' days'));
                    } else {
                        $newDateDues = date('Y-m-d', strtotime(date('Y-m-d') . ' + 2 days'));
                    }
                    $data = array(
                        'invoice_code' => generate_customer_invoice_code(),
                        'customer_id' => $customer_data->id,
                        'parent_id' => $fpartnerid,
                        'plan_id' => $plan_id,
                        'sub_plan_id' => $subplan_id,
                        'last_expiry_date' => date('Y-m-d H:i:s'),
                        'upcoming_expiry_date' => $normal_expiry,
                        'due_on' => $newDateDues,
                        'base_price' => $base_price,
                        'tax_amount' => $subplan_tax_price,
                        'invoice_status' => '0',
                        'camount_paid' => $subplan_total_amount,
                        'total_amount' => $subplan_total_amount,
                        'partner_amount_dedected' => '0',
                        'partner_earning_amount' => $my_commision_value,
                        'payment_mode' => '1'
                    );
                    //     $this->db->insert('tbl_invoices', $data);
                    DB::table('tbl_invoices')->insert($data);
                    //     $this->db->where('id', $dataofcustomer['custid'])->update('tbl_customers', ['plan_id' => $plan_id, 'subplan_id' => $subplan_id]);
                    DB::table('tbl_customers')->where('id', $dataofcustomer['custid'])->update(['plan_id' => $plan_id, 'subplan_id' => $subplan_id]);
                    //     $this->db->where('username', $customer_data->username)->update('radusergroup', ['groupname' => $plan_data->plan_code]);
                    DB::table('radusergroup')->where('username', $customer_data->username)->update(['groupname' => $plan_data->plan_code]);

                    disonnectusers($customer_data->username);
                    //     $this->session->set_flashdata('success', $renewMessage);
                    //     accesslog($this->session->flashdata('success'));
                    //     redirect('/user/showinvoices');
                    flash()->addSuccess($renewMessage);
                    accesslog($renewMessage);
                    return redirect()->route('showinvoices');
                } else {
                    return false;
                }
            } else {
                flash()->addError('Payment failed');
                return redirect()->route('renewal');
            }
        } else {
            flash()->addError('Invalid request');
            return redirect()->route('renewal');
        }
    }

    private function verifyRazorpaySignature($signature, $order_id, $payment_id)
    {
        $query = DB::table('tbl_pg')->select('Razorpay_merchentkey AS pkey', 'Razorpay_secret AS secret')->where('pg_id', 1)->first();
        $key_id = $query->pkey;
        $secret_key = $query->secret;
        $expected_signature = hash_hmac('sha256', $order_id . '|' . $payment_id, $secret_key);

        return ($signature == $expected_signature);
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
