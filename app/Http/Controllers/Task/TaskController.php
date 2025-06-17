<?php

namespace App\Http\Controllers\Task;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    public function __construct() {}

    public function checkSolution(Request $request)
    {
        $request->validate(
            [
                'code' => 'required|string'
            ]
        );
    }
}
