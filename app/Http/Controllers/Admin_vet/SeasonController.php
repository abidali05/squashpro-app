<?php

namespace App\Http\Controllers\Admin_vet;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class SeasonController extends Controller
{
    public function index(){
        return view('content.admin-vet.seasons.index');
    }
}
