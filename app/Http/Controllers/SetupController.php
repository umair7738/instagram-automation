<?php

namespace App\Http\Controllers;

use App\Support\SetupGuide;

class SetupController extends Controller
{
    public function index(SetupGuide $guide)
    {
        return view('setup.index', ['setup' => $guide->summary()]);
    }
}
