<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DashBoardController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        return view('dashboard', compact('user'));
    }
}
