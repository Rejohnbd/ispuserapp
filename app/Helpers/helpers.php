<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Jenssegers\Agent\Agent;

if (!function_exists('calculate_date_difference')) {
    function calculate_date_difference($start_date, $end_date)
    {
        $start_timestamp = strtotime($start_date);
        $end_timestamp = strtotime($end_date);

        // Calculate the total days
        $time_difference = $end_timestamp - $start_timestamp;
        $total_days = abs(round($time_difference / 86400));

        $current_date = time();

        // Calculate the used days
        $used_timestamp = $current_date - $start_timestamp;
        $used_days = abs(round($used_timestamp / 86400));

        // Calculate the remaining days
        $remaining_days = $total_days - $used_days;

        if ($start_timestamp == $end_timestamp) {
            return '<span class="badge bg-label-danger">--N/A--</span>';
        } else {
            return '<span class="badge bg-label-info">' . $used_days . ' Days || ' . $remaining_days . ' Days</span>';
        }
    }
}

if (!function_exists('getMacVendorName')) {
    function getMacVendorName($mac)
    {
        $mac_prefix = substr($mac, 0, 8);
        $query = DB::table('macdb')->where('oui', $mac_prefix)->first();
        // $query = $ci->db->get_where('macdb', array('oui' => $mac_prefix));
        if ($query) {
            return $query->vendor;
        } else {
            return '--N/A--';
        }
    }
}

if (!function_exists('getTodayUsage')) {
    function getTodayUsage($id)
    {
        // query(" SELECT SUM(`acctinputoctets`) AS total_input_octets, SUM(`acctoutputoctets`) AS total_output_octets, SUM(`acctinputoctets` + `acctoutputoctets`) AS total_inout_total_output_octets FROM `radacct` WHERE `acctstarttime` >= CURDATE() AND `acctstarttime` < CURDATE() + INTERVAL 1 DAY AND `username` IN (SELECT `username` FROM `tbl_customers` WHERE `id` = '$id') GROUP BY `username` ");
        $query = DB::select(DB::raw(" SELECT SUM(`acctinputoctets`) AS total_input_octets, SUM(`acctoutputoctets`) AS total_output_octets, SUM(`acctinputoctets` + `acctoutputoctets`) AS total_inout_total_output_octets FROM `radacct` WHERE `acctstarttime` >= CURDATE() AND `acctstarttime` < CURDATE() + INTERVAL 1 DAY AND `username` IN (SELECT `username` FROM `tbl_customers` WHERE `id` = '$id') GROUP BY `username` "));
        if ($query) {
            return $query;
        } else {
            return false;
        }
    }
}


if (!function_exists('convert_data_size')) {
    function convert_data_size($size_in_bits, $unit = 'auto', $decimal_places = 2)
    {
        if ($unit == 'auto') {
            if ($size_in_bits >= 1099511627776) { // 1 TB in bits
                $unit = 'tb';
            } elseif ($size_in_bits >= 1073741824) { // 1 GB in bits
                $unit = 'gb';
            } elseif ($size_in_bits >= 1048576) { // 1 MB in bits
                $unit = 'mb';
            } elseif ($size_in_bits >= 1024) { // 1 KB in bits
                $unit = 'kb';
            } else {
                $unit = 'bit';
            }
        }

        switch ($unit) {
            case 'tb':
                $size = $size_in_bits / 1099511627776;
                $extension = 'TB';
                break;
            case 'gb':
                $size = $size_in_bits / 1073741824;
                $extension = 'GB';
                break;
            case 'mb':
                $size = $size_in_bits / 1048576;
                $extension = 'MB';
                break;
            case 'kb':
                $size = $size_in_bits / 1024;
                $extension = 'KB';
                break;
            case 'bit':
                $size = $size_in_bits;
                $extension = 'bit';
                break;
            default:
                return 'Invalid unit';
        }

        return number_format($size, $decimal_places) . ' ' . $extension;
    }
}

if (!function_exists('getcustomersinvoicedetails')) {
    function getcustomersinvoicedetails($id)
    {
        // $query = $this->db->query("SELECT SUM(`total_amount`) AS totalinvoiceamount, SUM(`camount_paid`) AS totalpaidamount, (SUM(`total_amount`) - SUM(`camount_paid`)) AS totaldueamount, (SELECT `due_on` FROM tbl_invoices WHERE customer_id = '$id' ORDER BY `invoice_id` DESC LIMIT 1) AS duedate FROM `tbl_invoices` WHERE `customer_id` = '$id' ");
        $query = DB::select(DB::raw("SELECT SUM(`total_amount`) AS totalinvoiceamount, SUM(`camount_paid`) AS totalpaidamount, (SUM(`total_amount`) - SUM(`camount_paid`)) AS totaldueamount, (SELECT `due_on` FROM tbl_invoices WHERE customer_id = '$id' ORDER BY `invoice_id` DESC LIMIT 1) AS duedate FROM `tbl_invoices` WHERE `customer_id` = '$id' "));
        if ($query > 0) {
            return $query[0];
        } else {
            return false;
        }
    }
}

if (!function_exists('alldetailsfordashboard')) {
    function alldetailsfordashboard()
    {
        $user_id = Auth::user()->id;

        // $this->db->select('tbl_customers.*, tbl_plan.plan_name as planname, tbl_plan.plan_type as plan_type, tbl_subplan.name as subplanname, tbl_subplan.duration_type as duration_type, tbl_subplan.duration as duration, tbl_subplan.price as price')
        //     ->from('tbl_customers')
        //     ->join('tbl_subplan', 'tbl_subplan.subplan_id = tbl_customers.subplan_id', 'left')
        //     ->join('tbl_plan', 'tbl_plan.plan_id = tbl_customers.plan_id', 'left')
        //     ->where('tbl_customers.id', $user_id); // Corrected the where condition
        // $query = $this->db->get();

        // $query = DB::table('tbl_customers')
        //     ->join('tbl_subplan', 'tbl_subplan.subplan_id = tbl_customers.subplan_id', 'left')
        //     ->join('tbl_plan', 'tbl_plan.plan_id = tbl_customers.plan_id', 'left')
        //     ->select('tbl_customers.*, tbl_plan.plan_name as planname, tbl_plan.plan_type as plan_type, tbl_subplan.name as subplanname, tbl_subplan.duration_type as duration_type, tbl_subplan.duration as duration, tbl_subplan.price as price')
        //     ->where('tbl_customers.id', $user_id)
        //     ->get();
        // dd($query);

        // if ($query->num_rows() > 0) {
        //     return $query->row();
        // } else {
        //     return false;
        // }
    }
}

if (!function_exists('calculate_time_difference')) {
    function calculate_time_difference($start_datetime, $end_datetime)
    {
        $stoptime = strtotime($end_datetime);
        $current_time = strtotime($start_datetime);
        $duration = abs($stoptime - $current_time);
        $years = floor($duration / (365 * 24 * 60 * 60));
        $duration -= $years * 365 * 24 * 60 * 60;
        $months = floor($duration / (30 * 24 * 60 * 60));
        $duration -= $months * 30 * 24 * 60 * 60;
        $days = floor($duration / (24 * 60 * 60));
        $duration -= $days * 24 * 60 * 60;
        $hours = floor($duration / (60 * 60));
        $duration -= $hours * 60 * 60;
        $minutes = floor($duration / 60);
        $seconds = $duration % 60;

        $result = '';

        if ($years > 0) {
            $result .= "$years Y" . ($years > 1 ? 's' : '') . ', ';
        }
        if ($months > 0) {
            $result .= "$months M" . ($months > 1 ? 's' : '') . ', ';
        }
        if ($days > 0) {
            $result .= "$days D" . ($days > 1 ? 's' : '') . ', ';
        }
        if ($hours > 0) {
            $result .= "$hours H" . ($hours > 1 ? 's' : '') . ', ';
        }
        if ($minutes > 0) {
            $result .= "$minutes Min" . ($minutes > 1 ? 's' : '') . ', ';
        }
        // $result .= "$seconds S" . ($seconds > 1 ? 's' : '');
        return rtrim($result, ' ');
    }
}

if (!function_exists('formatdatetime')) {
    function formatdatetime($datetime)
    {
        return date('j M Y H:i:s A', strtotime($datetime));
    }
}

if (!function_exists('ptype')) {
    function ptype($pid)
    {
        // return $CI->db->where('id', $pid)->get('tbl_admins')->row()->user_type;
        $result = DB::table('tbl_admins')->where('id', $pid)->first();
        if (!empty($result)) {
            return $result->user_type;
        } else {
            return false;
        }
    }
}

if (!function_exists('calculate_upcoming_datetime')) {
    function calculate_upcoming_datetime($durationType, $durationValue, $ontimeMidnightFlag, $timestamp = null)
    {

        if ($timestamp == null) {
            $timestamp = time();
        }

        if ($ontimeMidnightFlag == 0) {
            switch ($durationType) {
                case 0: // Days
                    // Calculate the date based on days
                    $newTimestamp = strtotime("+{$durationValue} days", $timestamp);
                    break;
                case 1: // Months
                    // Calculate the date based on months
                    $newTimestamp = strtotime("+{$durationValue} months", $timestamp);
                    break;
                    // Add more cases for other duration types as needed
                default:
                    return false;
            }

            // Set the time portion to 11:59:00
            $newTimestamp = strtotime('23:59:00', strtotime(date('j M Y ', $newTimestamp)));
        } else {
            switch ($durationType) {
                case 0: // Days
                    // Calculate the date and time based on days
                    $newTimestamp = strtotime("+{$durationValue} days", $timestamp);
                    break;
                case 1: // Months
                    // Calculate the date and time based on months
                    $newTimestamp = strtotime("+{$durationValue} months", $timestamp);
                    break;
                    // Add more cases for other duration types as needed
                default:
                    return false;
            }
        }

        return date('j M Y H:i:s', $newTimestamp);
    }
}

if (!function_exists('generate_customer_invoice_code')) {

    function generate_customer_invoice_code($prefix = '', $sn = '', $parent = '')
    {
        // Fetch the last code from the database
        // $query = $CI->db->where('parent_id', $parent)->select_max('invoice_code')->get('tbl_invoices');
        // $last_code_row = $query->row();
        $last_code_row = DB::table('tbl_invoices')->where('parent_id', $parent)->orderBy('invoice_id', 'desc')->first();

        if ($last_code_row && $last_code_row->invoice_code !== null) {
            // Extract the numeric part of the last code and increment it
            $last_number = (int)preg_replace('/[^0-9]/', '', substr($last_code_row->invoice_code, strlen($prefix)));
            $next_number = $last_number + 1;

            // Build the next code with the provided prefix and sn
            $next_code = $prefix . str_pad($next_number, 8, '0', STR_PAD_LEFT);
        } else {
            // Use $sn as the next code with prefix and str_pad
            $next_code = $prefix . str_pad($sn, 8, '0', STR_PAD_LEFT);
        }

        return $next_code;
    }
}

if (!function_exists('disonnectusers')) {
    function disonnectusers($username)
    {
        // $details = $CI->db->where('username', $username)->where('acctstoptime IS NULL', null, false)->get('radacct');
        $details = DB::table('radacct')->where('username', $username)->whereNull('acctstoptime')->first();

        if (!empty($details)) {
            // $query = $details->row();
            $query = $details;
            $nasip = $query->nasipaddress;
            // $nas = $CI->db->where('nasname', $nasip)->get('nas')->row();
            $nas = DB::table('nas')->where('nasname', $nasip)->first();
            $secret = $nas->secret;
            $ports = $nas->ports;
            $acct = $query->acctsessionid;
            $framedIpAddress = $query->framedipaddress;
            shell_exec('echo User-Name=' . $username . ',Acct-Session-Id=' . $acct . ',Framed-IP-Address=' . $framedIpAddress . ' | radclient -x ' . $nasip . ':' . $ports . ' disconnect ' . $secret . '');
            return true;
        } else {
            return false;
        }
    }
}
if (!function_exists('accesslog')) {
    function accesslog($message, $id = null)
    {
        $agent = new Agent();
        $clientRequest = Request();

        $data = array(
            'ipaddress' => $clientRequest->getClientIp(),
            'browser' => $agent->browser(),
            'os' => $agent->platform(),
            'datetime' => date('Y-m-d H:i:s'),
            'msg' => $message,
            'customer_id' => $id,
            'perform_by' => Auth::user()->id,
        );
        DB::table('tbl_authlogs')->insert($data);
    }
}

if (!function_exists('get_user_status')) {
    function get_user_status($username)
    {
        $query = DB::select("SELECT * FROM radacct WHERE username = ? AND acctstoptime IS NULL", array($username));

        if (!empty($query)) {
            return '<span class="badge bg-label-success blink_me">Online</span>';
        } else {
            $query = collect(DB::select("SELECT MAX(acctstoptime) AS max_stoptime FROM radacct WHERE username = ?", array($username)))->first();

            if ($query->max_stoptime !== null) {
                $stoptime = strtotime($query->max_stoptime);
                $current_time = time();
                $duration = $current_time - $stoptime;

                $years = floor($duration / (365 * 24 * 60 * 60));
                $duration -= $years * 365 * 24 * 60 * 60;
                $months = floor($duration / (30 * 24 * 60 * 60));
                $duration -= $months * 30 * 24 * 60 * 60;
                $days = floor($duration / (24 * 60 * 60));
                $duration -= $days * 24 * 60 * 60;
                $hours = floor($duration / (60 * 60));
                $duration -= $hours * 60 * 60;
                $minutes = floor($duration / 60);
                $seconds = $duration % 60;

                $result = '';
                if ($years > 0) {
                    $result .= "$years Y" . ($years > 1 ? 's' : '') . ', ';
                }
                if ($months > 0) {
                    $result .= "$months M" . ($months > 1 ? 's' : '') . ', ';
                }
                if ($days > 0) {
                    $result .= "$days D" . ($days > 1 ? 's' : '') . ', ';
                }
                if ($hours > 0) {
                    $result .= "$hours H" . ($hours > 1 ? 's' : '') . ', ';
                }
                if ($minutes > 0) {
                    $result .= "$minutes Min" . ($minutes > 1 ? 's' : '') . ', ';
                }
                $result .= "$seconds S" . ($seconds > 1 ? 's' : '');
                return '<span class="badge bg-label-danger blink_me">Offline (' . rtrim($result) . ')</span>';
            } else {
                return '<span class="badge bg-label-danger blink_me">Offline</span>';
            }
        }
    }
}

if (!function_exists('smssender')) {
    function smssender($parent = "", $c_id = "", $mobile = "", $message = "", $templateid = "")
    {
        $query = DB::table('tbl_alertinfo')->where('sms_active_status', 1)->where('id', 1)->first();
        if (!is_null($query)) {

            $fast2sms = smshealper($message, $mobile, $templateid);

            DB::table('tbl_smslogs')->insert([
                'a_id' => $parent,
                'customer_id' => $c_id,
                'mobile_no' => $mobile,
                'message_body' => $message,
                'response_message' => $fast2sms,
            ]);
        } else {
            return false;
        }
    }
}

if (!function_exists('smshealper')) {
    function smshealper($message = "", $numbers = "", $templateid = "")
    {
        $gatewayres = "";

        $query = DB::table('tbl_alertinfo')->where('sms_active_status', 1)->where('id', 1)->first();

        if (is_null($query)) {
            // Return an error if the gateway information is not set
            return false;
        } else {
            $method = $query->sms_gateway_method;
            $getway_url = $query->sms_gateway_url;

            $datam = array(
                '{message}' => urlencode($message),
                '{mobile}' => $numbers,
                '{templateid}' => $templateid
            );

            $url = replacePlaceholders($getway_url, $datam);
            // dd($url);
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_URL, $url);
            // curl_setopt($curl, CURLOPT_HEADER, false);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            $response = curl_exec($curl);
            $err = curl_error($curl);
            curl_close($curl);

            if ($err) {
                $gatewayres = "cURL Error #:" . $err;
            } else {
                $gatewayres = $response;
            }
            return $gatewayres;
        }
    }
}

if (!function_exists('replacePlaceholders')) {
    function replacePlaceholders($template, $replaceArray)
    {
        return str_replace(
            array_keys($replaceArray),
            array_values($replaceArray),
            $template
        );
    }
}


if (!function_exists('loadtemplate')) {
    function loadtemplate($templateid)
    {
        $template = DB::table('tbl_smstemplates')->where('template_id', $templateid)->first();

        if (!is_null($template)) {
            return $template;
        } else {
            return false;
        }
    }
}

if (!function_exists('whatap_message_healper')) {
    function whatap_message_healper($number, $message)
    {
        $gatewayres = "";
        // Fetch API configuration from the database
        // $query = $CI->db->where('whatsapp_active_status', '1')->where('id', 1)->get('tbl_alertinfo');
        $query =  DB::table('tbl_alertinfo')->where('whatsapp_active_status', '1')->where('id', 1)->first();
        if (!is_null($query)) {

            $api_url = $query->whatsapp_gateway_url;
            // Data to be sent in the POST request as form data
            $datam = array(
                '{message}' => urlencode($message),
                '{mobile}' => $number,
            );

            $url = replacePlaceholders($api_url, $datam);

            $curl = curl_init();
            curl_setopt($curl, CURLOPT_URL, $url);
            // curl_setopt($curl, CURLOPT_HEADER, false);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            $response = curl_exec($curl);
            $err = curl_error($curl);
            curl_close($curl);

            // Return the API response (you may want to handle this response further)
            if ($err) {
                $gatewayres = "cURL Error #:" . $err;
                whatsapp_logs($number, $message, $response, Auth::user()->parent_id, Auth::user()->id);
            } else {
                $gatewayres = $response;
                whatsapp_logs($number, $message, $response, Auth::user()->parent_id, Auth::user()->id);
            }
            return $gatewayres;
        } else {
            return "No configuration found in the database.";
        }
    }
}

if (!function_exists('whatsapp_logs')) {
    function whatsapp_logs($mobile, $message, $response, $parent = "", $c_id = "")
    {
        DB::table('tbl_whatslogs')->insert([
            'a_id' => $parent,
            'customer_id' => $c_id,
            'mobile_no' => $mobile,
            'message_body' => $message,
            'response_message' => $response,
        ]);
    }
}
