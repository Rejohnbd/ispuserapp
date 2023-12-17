<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Razorpay\Api\Api;
use Validator;
use Ixudra\Curl\Facades\Curl;

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

        return view('web.renewal', compact('planbyop', 'subplanbyop', 'subplanprice', 'subplanprice', 'pgdetails'));
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

                        $datam = array(
                            '{username}' => $customer_data->username,
                            '{invoice_no}' => $data['invoice_code'],
                            '{invoice_amount}' => $subplan_total_amount
                        );
                        $templateInfo = loadtemplate(8);
                        // Use the helper function to replace placeholders
                        $message = replacePlaceholders($templateInfo->template_content, $datam);
                        smssender($customer_data->partner_id, $customer_data->id, $customer_data->mobno, $message, $templateInfo->dlt_template_id);
                        whatap_message_healper($customer_data->mobno, $message);

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

                    $datam = array(
                        '{username}' => $customer_data->username,
                        '{invoice_no}' => $data['invoice_code'],
                        '{invoice_amount}' => $subplan_total_amount
                    );
                    $templateInfo = loadtemplate(8);
                    // Use the helper function to replace placeholders
                    $message = replacePlaceholders($templateInfo->template_content, $datam);
                    smssender($customer_data->partner_id, $customer_data->id, $customer_data->mobno, $message, $templateInfo->dlt_template_id);
                    whatap_message_healper($customer_data->mobno, $message);


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

                    $datam = array(
                        '{username}' => $customer_data->username,
                        '{invoice_no}' => $data['invoice_code'],
                        '{invoice_amount}' => $subplan_total_amount
                    );
                    $templateInfo = loadtemplate(8);
                    // Use the helper function to replace placeholders
                    $message = replacePlaceholders($templateInfo->template_content, $datam);
                    smssender($customer_data->partner_id, $customer_data->id, $customer_data->mobno, $message, $templateInfo->dlt_template_id);
                    whatap_message_healper($customer_data->mobno, $message);


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

    public function phonepeCheckout(Request $request)
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
            $query = DB::table('tbl_pg')->select('phonepe_merchant_id', 'phonepe_salt_key', 'phonepe_salt_index', 'phonepe_url')->where('pg_id', 1)->first();

            if (is_null($query->phonepe_merchant_id) || is_null($query->phonepe_salt_key) || is_null($query->phonepe_salt_index) || is_null($query->phonepe_url)) {
                flash()->addError('Please check payment gateway settings');
                return redirect()->back();
            }


            $subplan_id = $request->subplan_id;
            $amount = DB::table('tbl_subplan')->where('subplan_id', $subplan_id)->first('total_price');
            $amount = (int)$amount->total_price * 100;
            // dd($query->phonepe_merchant_id);
            $data = array(
                'merchantId' => $query->phonepe_merchant_id,
                'merchantTransactionId' => $odid,
                'merchantUserId' => 'MUID' . time(),
                'amount' => $amount,
                'redirectUrl' => route('phonepe-callback'),
                'redirectMode' => 'POST',
                'callbackUrl' => route('phonepe-callback'),
                'mobileNumber' => $request->mobile,
                'paymentInstrument' => [
                    'type' => 'PAY_PAGE',
                ]
            );
            // dd($query->phonepe_url);
            $encode = base64_encode(json_encode($data));
            $string = $encode . '/pg/v1/pay' . $query->phonepe_salt_key;
            $sha256 = hash('sha256', $string);

            $finalXHeader = $sha256 . '###' . $query->phonepe_salt_index;
            // dd($finalXHeader);
            // $response = Curl::to($query->phonepe_url)
            //     ->withHeader('Content-Type:application/json')
            //     ->withHeader('X-VERIFY:' . $finalXHeader)
            //     ->withData(json_encode(['request' => $encode]))
            //     ->post();

            $headers = [
                'Content-Type' => 'application/json',
                'X-VERIFY' => $finalXHeader
            ];

            $response = Http::withHeaders($headers)->post($query->phonepe_url, ['request' => $encode]);
            $rData = json_decode($response);

            $advcheck = $request->has('advancedrenew') ? 1 : 0;

            $dataofcustomer = array(
                'partnerid' => $request->partner_id,
                'custid' => $request->id,
                'custname' => $request->name,
                'mobile' => $request->mobile,
                'email' => $request->email,
                'subplan_id' => $subplan_id,
                'advcheck' => $advcheck
            );

            session()->put('privatedetails', $dataofcustomer);
            return redirect()->to($rData->data->instrumentResponse->redirectInfo->url);
        }
    }

    public function phonepeCallback(Request $request)
    {
        $query = DB::table('tbl_pg')->select('phonepe_merchant_id', 'phonepe_merchant_user_id', 'phonepe_salt_key', 'phonepe_salt_index', 'phonepe_url')->where('pg_id', 1)->first();
        $saltKey = $query->phonepe_salt_key;
        $saltIndex = $query->phonepe_salt_index;

        $finalXHeader = hash('sha256', '/pg/v1/status/' . $request->merchantId . '/' . $request->transactionId . $saltKey) . '###' . $saltIndex;

        // $key = '53201aea-9942-482f-952a-7bc6c6102453'; // KEY
        // $key_index = 1; // KEY_INDEX
        // $response = $_POST; // FETCH DATA FROM DEFINE METHOD, IN THIS EXAMPLE I AM DEFINING POST WHILE I AM SENDING REQUEST
        // $final_x_header = hash("sha256", "/pg/v1/status/" . $response['merchantId'] . "/" . $response['transactionId'] . $key_index) . "###" . $key;
        // $url = "https://api-preprod.phonepe.com/apis/pg-sandbox/pg/v1/status/" . $response['merchantId'] . "/" . $response['transactionId']; // <TESTING URL>
        // $headers = array(
        //     "Content-Type: application/json",
        //     "accept: application/json",
        //     "X-VERIFY: " . $final_x_header,
        //     "X-MERCHANT-ID:" . $response['merchantId']
        // );
        // $curl = curl_init($url);
        // curl_setopt($curl, CURLOPT_URL, $url);
        // curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'GET');
        // curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        // curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        // $resp = curl_exec($curl);
        // curl_close($curl);
        // $responsePayment = json_decode($resp, true);

        $headers = [
            'Content-Type' => 'application/json',
            'X-VERIFY' => $finalXHeader,
            'X-MERCHANT-ID' => $request->transactionId
        ];

        $response = Http::withHeaders($headers)->get('https://api-preprod.phonepe.com/apis/pg-sandbox/pg/v1/status/' . $request->merchantId . '/' . $request->transactionId);
        dd(json_decode($response));

        // $response = Curl::to('https://api-preprod.phonepe.com/apis/pg-sandbox/pg/v1/status/' . $input['merchantId'] . '/' . $input['transactionId'])
        //     ->withHeader('Content-Type:application/json')
        //     ->withHeader('accept:application/json')
        //     ->withHeader('X-VERIFY:' . $finalXHeader)
        //     ->withHeader('X-MERCHANT-ID:' . $input['transactionId'])
        //     ->get();

        // dd($request->all(), json_decode($response));
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
        $result = DB::table('tbl_subscriberportalsettings')->where('settings_id', 1)->first('is_sub_pre_usage');
        $status = $result->is_sub_pre_usage;
        if ($status) {
            $user_id = Auth::user()->id;
            $sessionhistory = DB::select(DB::raw(" SELECT * FROM `radacct` WHERE username IN (SELECT username FROM tbl_customers WHERE id = '$user_id') OR username IN (SELECT macaddress FROM tbl_customers WHERE id = '$user_id') ORDER BY `radacctid` DESC "));
            return view('web.session_history', compact('sessionhistory', 'status'));
        }
        return view('web.session_history', compact('status'));
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
        $result = DB::table('tbl_subscriberportalsettings')->where('settings_id', 1)->first('is_sub_cur_us');
        $status = $result->is_sub_cur_us;

        if ($status) {
            $user_id = Auth::user()->id;
            $authlogs = DB::select(DB::raw("SELECT * FROM `radpostauth` WHERE username IN (SELECT username FROM tbl_customers WHERE id = '$user_id') OR username IN (SELECT macaddress FROM tbl_customers WHERE id = '$user_id') ORDER BY radpostauth.id DESC LIMIT 200 "));
            return view('web.authlogs', compact('authlogs', 'status'));
        }

        return view('web.authlogs', compact('status'));
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
        $result = DB::table('tbl_subscriberportalsettings')->where('settings_id', 1)->first('is_sub_invoice');
        $status = $result->is_sub_invoice;

        if ($status) {
            $user_id = Auth::user()->id;
            $showinvoices = DB::table('tbl_invoices')
                ->leftJoin('tbl_plan', 'tbl_invoices.plan_id', '=', 'tbl_plan.plan_id')
                ->leftJoin('tbl_subplan', 'tbl_invoices.sub_plan_id', '=', 'tbl_subplan.subplan_id')
                ->where('customer_id', $user_id)
                ->select('tbl_invoices.*', 'tbl_plan.plan_name as planname', 'tbl_subplan.name as subplanname')
                ->orderBy('invoice_id', 'DESC')
                ->get();

            return view('web.showinvoices', compact('status', 'showinvoices'));
        }

        return view('web.showinvoices', compact('status'));
    }

    public function printInvoice($id)
    {
        try {
            $invoiceshow = DB::table('tbl_invoices')
                ->leftJoin('tbl_customers', 'tbl_invoices.customer_id', '=', 'tbl_customers.id')
                ->leftJoin('tbl_admins', 'tbl_admins.id', '=', 'tbl_invoices.parent_id')
                ->leftJoin('tbl_plan', 'tbl_invoices.plan_id', '=', 'tbl_plan.plan_id')
                ->leftJoin('tbl_subplan', 'tbl_invoices.sub_plan_id', '=', 'tbl_subplan.subplan_id')
                ->orderBy('tbl_invoices.invoice_id', 'desc')
                ->where('invoice_id', decrypt($id))
                ->select('tbl_invoices.*', 'tbl_customers.customer_id as cid', 'tbl_admins.name as partner_name', 'tbl_customers.username as cuname', 'tbl_customers.name as customername', 'tbl_customers.mobno as mobile', 'tbl_customers.email as email', 'tbl_customers.mobno as mobile', 'tbl_customers.billaddress as caddress', 'tbl_plan.plan_name as planname', 'tbl_subplan.name as suplanname')
                ->first();

            if (!empty($invoiceshow)) {
                $partnercomp = DB::table('tbl_admins')->where('id', $invoiceshow->parent_id)->first('billing_company');
                $billcomprofile = DB::table('tbl_companysettings')->where('company_id', $partnercomp->billing_company)->first();

                if ($billcomprofile->inv_template == '0') {
                    return view('web.finance.template0', compact('invoiceshow', 'billcomprofile'));
                } else if ($billcomprofile->inv_template == '1') {
                    return view('web.finance.template1', compact('invoiceshow', 'billcomprofile'));
                } else if ($billcomprofile->inv_template == '2') {
                    return view('web.finance.template2', compact('invoiceshow', 'billcomprofile'));
                } else if ($billcomprofile->inv_template == '3') {
                    return view('web.finance.template3', compact('invoiceshow', 'billcomprofile'));
                } else {
                    return view('web.finance.template0', compact('invoiceshow', 'billcomprofile'));
                }
            }

            flash()->addError('Invoice Not Found');
            return redirect()->back();
        } catch (\Throwable $th) {
            flash()->addError('Invoice Not Found');
            return redirect()->back();
        }
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
        $result = DB::table('tbl_subscriberportalsettings')->where('settings_id', 1)->first(['is_sub_ticket', 'al_sub_cr_ticket']);
        $status = $result->is_sub_ticket;
        $cr_status = $result->al_sub_cr_ticket;

        if ($status) {
            $templates = DB::table('tbl_complainttype')->where('parent_id', Auth::user()->partner_id)->get();
            $complaints = DB::table('tbl_complaints')
                ->leftJoin('tbl_admins', 'tbl_admins.id', '=', 'tbl_complaints.assigned_id')
                ->leftJoin('tbl_complainttype', 'tbl_complainttype.complainttype_id', '=', 'tbl_complaints.complainttype_id')
                ->where('tbl_complaints.customer_id', Auth::user()->id)
                ->select('tbl_complaints.*', 'tbl_admins.name AS assigned_name', 'tbl_complainttype.complaint_type')
                ->orderBy('tbl_complaints.complaint_id', 'DESC')
                ->get();
            return view('web.complaint', compact('templates', 'complaints', 'status', 'cr_status'));
        }

        $templates = DB::table('tbl_complainttype')->where('parent_id', Auth::user()->partner_id)->get();
        return view('web.complaint', compact('templates', 'status', 'cr_status'));
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
            $complain_no = $this->generate_unique_codeforcomplain();

            DB::table('tbl_complaints')->insert([
                'complain_no' => $complain_no,
                'parent_id' => Auth::user()->partner_id,
                'priority' => $request->priority,
                'customer_id' => Auth::user()->id,
                'complainttype_id' =>  $request->template_id,
                'message' => $request->complaintscomments,
            ]);

            $datam = array(
                '{username}' => Auth::user()->username,
                '{complaint_number}' => $complain_no
            );
            $templateInfo = loadtemplate(3);
            // Use the helper function to replace placeholders
            $message = replacePlaceholders($templateInfo->template_content, $datam);
            smssender(Auth::user()->partner_id, Auth::user()->id, Auth::user()->mobno, $message, $templateInfo->dlt_template_id);
            whatap_message_healper(Auth::user()->mobno, $message);

            flash()->addSuccess('Complain Submitted Successfully.');
            return redirect()->back();
        }
    }

    public function generate_unique_codeforcomplain()
    {
        $result = DB::table('tbl_complaints')->latest()->first('complain_no');

        // Extract the numeric part of the last code and increment it
        $last_number = (int)substr($result->complain_no, 2);
        $next_number = $last_number + 1;

        // done add PL then gen nex code // tested done 12:52
        $next_code = 'CL' . str_pad($next_number, 3, '0', STR_PAD_LEFT);
        return $next_code;
    }

    public function setting()
    {
        $result = DB::table('tbl_subscriberportalsettings')->where('settings_id', 1)->first('is_sub_ch_pass');
        $status = $result->is_sub_ch_pass;
        return view('web.setting', compact('status'));
    }

    public function settingUpdate(Request $request)
    {
        $validated = $request->validate([
            'old_password' => 'required',
            'new_password' => 'required|min:6',
        ], [
            'old_password.required' => 'Old Password is Required',
            'new_password.required' => 'New Password is Required',
            'new_password.min' => 'New Password Length Minimum 6',
        ]);

        if ($request->old_password == Auth::user()->password) {

            DB::table('tbl_customers')->where('id', Auth::user()->id)->update([
                'password'  => $request->new_password
            ]);

            $datam = array(
                '{username}' => Auth::user()->username,
                '{new_password}' => $request->new_password
            );
            $templateInfo = loadtemplate(9);
            // Use the helper function to replace placeholders
            $message = replacePlaceholders($templateInfo->template_content, $datam);
            smssender(Auth::user()->partner_id, Auth::user()->id, Auth::user()->mobno, $message, $templateInfo->dlt_template_id);
            whatap_message_healper(Auth::user()->mobno, $message);

            flash()->addSuccess('Updated Successfully.');
            return redirect()->back();
        }
        flash()->addError('Old Password Not Matched');
        return redirect()->back();
    }
}
