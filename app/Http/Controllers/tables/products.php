<?php

namespace App\Http\Controllers\tables;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class products extends Controller
{
  public function index()
  {
    return view('content.tables.tables-products');
  }
}
