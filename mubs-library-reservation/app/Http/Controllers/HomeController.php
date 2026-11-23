<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class HomeController extends Controller
{
    /**
     * Show the library home page.
     */
    public function index(): View
    {
        return view('welcome');
    }
}
