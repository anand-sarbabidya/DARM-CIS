<?php

use App\Helper\ZKTService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    $device = new ZKTService("192.168.68.201");
    $device->connect();
//    $data = DB::table('users')->get();
    $attendance = $device->getAttendance();
   dd($attendance);
//    return request()->ip();
});

Route::get('/time', function (){
    $dt = date('Y-m-d H:i:s');
    dd($dt);
});
Route::get('/clear', function() {
    $exitCode = Artisan::call('config:clear');
    $exitCode = Artisan::call('config:cache');
    $exitCode = Artisan::call('cache:clear');
    $exitCode = Artisan::call('view:clear');
    $exitCode = Artisan::call('route:clear');

    return 'DONE'; //Return anything
});

Route::get('/getusers', [\App\Http\Controllers\AttendanceController::class, 'GetUsers']);
Route::get('/deviceattendance', [\App\Http\Controllers\AttendanceController::class, 'GetAttendanceFromDevice']);
Route::get('/insertattendance', [\App\Http\Controllers\AttendanceController::class, 'InsertAttendance']);
Route::get('/getuserattendance/{id}', [\App\Http\Controllers\AttendanceController::class, 'GetAttendance']);
