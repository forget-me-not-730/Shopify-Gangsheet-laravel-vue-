<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

class SettingController extends Controller
{
    function index(){
        return inertia('Admin/Settings');
    }
}
