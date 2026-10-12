<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\Analytics;

class AnalyticsController extends Controller
{
    public function __invoke()
    {
        return view('admin.analytics', ['data' => Analytics::build()]);
    }
}
