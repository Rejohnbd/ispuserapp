<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

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

        if ($start_timestamp === $end_timestamp) {
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
        if ($unit === 'auto') {
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
