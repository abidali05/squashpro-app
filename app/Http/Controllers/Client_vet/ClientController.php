<?php

namespace App\Http\Controllers\Client_vet;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ClientController extends Controller
{
    public function index(){
        return view('content.client-vet.clients.index');
    }
}
