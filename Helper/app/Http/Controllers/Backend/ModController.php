<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ModController extends Controller
{
    public function dashboard(){
        return view('mod.dashboard');
    }
}
