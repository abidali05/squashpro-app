<?php

namespace App\Http\Controllers\Admin_vet;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ContractController extends Controller
{
    public function index(){
        return view('content.admin-vet.contracts.index');
    }
}
