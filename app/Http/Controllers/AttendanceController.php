<?php

namespace App\Http\Controllers;

use App\Helper\ZKTService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AttendanceController extends Controller
{
    public $device;

    public function __construct()
    {
        $this->device = new ZKTService("192.168.68.201");
        $this->device->connect();
    }

    public function GetUsers()
    {
        $users = $this->device->getUser();

        dd($users);
    }

    public function GetAttendanceFromDevice()
    {
        $attendance = $this->device->getAttendance();

        $att_data = [];
        foreach ($attendance as $key => $value) {
            if (date('Y-m') == date('Y-m', strtotime($value['timestamp']))) {
                $att_data[] = $value;
            }
        }
        dd($att_data);
    }

    public function InsertAttendance()
    {
        $attendance = $this->device->getAttendance();

        $att_data = [];
        foreach ($attendance as $key => $value){
            if(date('Y-m') == date('Y-m', strtotime($value['timestamp']))) {

                $time = explode(" ", $value['timestamp']);
                $time_join = explode(":",$time[1]);
                $att_data[] = [
                    "user_id" => $value['id'],
                    "state" => $value['state'],
                    "date" => date('Y-m-d', strtotime($value['timestamp'])),
                    "time" => $time_join[0]+":"+$time_join[1],
                    "type" => $value['type'],
                    "created_at" => date("Y-m-d h:i:s")
                ];
            }
        }

        $insert = DB::table('user_attendance')->insert($att_data);

        if($insert){
            return true;
        }

        return false;
    }

    public function GetAttendance($id)
    {
        $data = DB::table('user_attendance')
            ->leftJoin('users', 'users.unq_id', 'user_attendance.user_id')
            ->where('user_attendance.user_id', $id)
            ->select('user_attendance.*', DB::raw("COUNT(user_attendance.id) as total"), 'users.name')
            ->groupBy('user_attendance.date')
            ->get();


        $att = [];
        foreach ($data as $key => $value){
            $chk_out_time = "";
            $hours = "";
            $time_late = "";
            $early_leaving="";
            $default_check_out_time = strtotime("18:00:00");
            $default_check_in_time = strtotime("10:00:00");
            $default_working_hours  = strtotime("8:00:00");
            $late_leaving="";
            $working_hours="";
            $under_time="";
            $overtime="";
            $attendance_status="";
            $minutes = "";
            $check_in_time_1="";
            $check_out_time_1="";



            $check_in = DB::table('user_attendance')
                ->where('user_id', $value->user_id)
                ->where('date', $value->date)
                ->select('id','user_id','date','time')
                ->orderBy('id', 'asc')
                ->first();


            if($value->total > 1) {
                $check_out = DB::table('user_attendance')
                    ->where('user_id', $value->user_id)
                    ->where('date', $value->date)
                    ->select('id','user_id','date','time')
                    ->orderBy('id', 'desc')
                    ->first();

                $chk_out_time = $check_out->time;
                $check_out_time = strtotime("$chk_out_time");
                $check_in_time = strtotime("$check_in->time");

                $minutes2= round(abs($check_out_time - $check_in_time) / 60,2);
                $working_hours = floor($minutes2 / 60) . ':' . round(($minutes2 - floor($minutes2 / 60) * 60));
//                $time = strtotime($newformat);
//                $working_hours = date('H:i',$time);
//
//                dd($newformat);

                $minutes1= round(abs($check_in_time - $default_check_in_time) / 60,2);
                $time_late = floor($minutes1/ 60) . ':' . round(($minutes1 - floor($minutes1 / 60) * 60));
//                $time = strtotime($newformat);
//                $time_late = date('H:i',$time);

                //Early Leave & Late Leave Time Calculation
//                if($check_out_time < $default_check_out_time) {
//                    $minutes2 = round(abs($default_check_out_time - $check_out_time) / 60, 2);
//                    $early_leaving = floor($minutes2 / 60) . ':' . ($minutes2 - floor($minutes2 / 60) * 60);
//                }
//                else {
//                    $minutes3 = round(abs($check_out_time - $default_check_out_time) / 60, 2);
//                    $late_leaving = floor($minutes3 / 60) . ':' . ($minutes3 - floor($minutes3 / 60) * 60);
//                }
//                //Under_time & Overtime
                if (strtotime("$working_hours") > $default_working_hours){
                    $minutes4 = round(abs(strtotime("$working_hours") - $default_working_hours) / 60, 2);
                    $overtime = floor($minutes4 / 60) . ':' . round(($minutes4 - floor($minutes4 / 60) * 60));
//                    $time = strtotime($newformat);
//                    $overtime = date('H:i',$time);
                }
                else{
                    $minutes5 = round(abs($default_working_hours - strtotime("$working_hours")) / 60, 2);
                    $early_leaving = floor($minutes5 / 60) . ':' . round(($minutes5 - floor($minutes5 / 60) * 60));
//                    $time = strtotime($newformat);
//                    $early_leaving = date('H:i',$time);
                }
                //Clock_in and Clock_out in hours and minutes
                $time = strtotime($chk_out_time);
                $check_out_time_1 = date('H:i',$time);
                $time = strtotime($check_in->time);
                $check_in_time_1 = date('H:i',$time);
            }
            $att[] = [
                "employee_id" => $id,
//              "user_name" => $value->name,
                "attendance_date" => date("Y-m-d", strtotime($check_in->date)),
                "clock_in" => $check_in_time_1,
                "clock_in_ip"=> "",
                "clock_out" => $check_out_time_1,
                "clock_out_ip"=> "",
                "clock_in_out"=> 1,
                'time_late' => $time_late,
                'early_leaving' => $early_leaving,
                "overtime" => $overtime,
                "total_work" => $working_hours,
                "total_rest"=> "",
                "attendance_status"=>"present",
                "type" => 1,
                "created_at" => date("Y-m-d h:i:s")

            ];
        }

//        echo "<pre>";
//        var_dump($att);
//        echo "</pre>";
//
//        exit();

        $insert = DB::table('attendances')->insert($att);

        if($insert){
            return true;
        }
        return false;
        echo "<pre>";
        var_dump($att);
        echo "</pre>";
    }
}
