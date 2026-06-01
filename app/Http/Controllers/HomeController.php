<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;

class HomeController extends Controller
{
    public function showHomePage(): RedirectResponse
    {
        return redirect(route('tasks.show'));
    }
}
