<?php

namespace App\Http\Controllers;
use App\HTTp\Libraries\JWT\JWTUtils;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Login;
// use Illuminate\Http\Request;
use App\Libraries\Bcrypt;


class TestController extends Controller
{


    function test(Request $request)
    {
        return response()->json(["status"=>"success"]);
    }
}
