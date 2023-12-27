<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Http\Libraries\JWT\JWTUtils;
use App\Http\Libraries\Bcrypt;


class AuthController extends Controller
{


    private $mongo;
    private $db;
    private $jwtUtils;
    private $bcrypt;

    private $UsernameFromAccounts;


    public function __construct()
    {
        $this->mongo = new \MongoDB\Client("mongodb://iiot-center2:%24nc.ii0t%402o2E@10.0.0.7:27017/?authSource=admin");
        $this->db = $this->mongo->selectDatabase("IPSS_B7");
        $this->jwtUtils = new JWTUtils();
        $this->bcrypt = new Bcrypt(10);
    }

    private function receiveName($Username)
    {
        try {
            return $Username;
        } catch (\Exception $e) {

            return null;
        }
    }

    // Login function
    function login(Request $request)
    {

        try {
            // Require username and password
            $validators = Validator::make(
                $request -> all(),
                [
                    'Username' => 'required | string',
                    'Password' => 'required | string | min:1 | max:255',
                ]
            );

            if ($validators -> fails()) {
                return response()->json([
                    "status" => "error",
                    "state" => false,
                    "message" => "Bad request",
                    "data" => [
                        [
                            "validator" => $validators -> errors()
                        ]
                    ]
                        ], 400);
            }

            // declare variables


            // $username =  $this -> receiveName(UsernameFromAccounts);
            // $GLOBALS['username'] = $request->Username;

            $username = $request->Username;
            $password = $request->Password;



            // set filter for search
            $filter = ["Username" => $username];

            $result = $this->db->selectCollection("Accounts")->find($filter); //find likes a select, if add variable, it'll like a where
            $user = array();
            foreach ($result as $doc) \array_push($user, $doc);

            // check username is in system
            if (count($user) === 0){
                return response()->json([
                    "status" => "error",
                    "state" => false,
                    "message" => "The username does not exist",
                    "data" => [],
                ]);
            }

            // check password is correct?
            $isPass  = password_verify($password, $user[0]->Password);
            if (!$isPass){
                return response()->json([
                    "status" => "error",
                    "state" => false,
                    "message" => "Password incorrect",
                    "data" => []
                ]);
            }

            // create token
            \date_default_timezone_set('Asia/Bangkok');
            $dt = new \DateTime();
            $payload = array(
                "username" => $user[0] -> Username,
                "name" => $user[0] -> Name,
                "iat" => $dt -> getTimestamp(),
                "exp" => $dt -> modify('+ 5hours') -> getTimestamp(),
            );

            $token = $this->jwtUtils->generateToken($payload);
            return response() -> json([
                "status" => "success",
                "state" => true,
                "message" => "Login success!!",
                "data" => [
                    [
                        "Name"=>$user[0]->Name,
                        "Username" =>$user[0]->Username,
                        "Role" => $user[0]->Role,
                        "token" => $token,
                        // "name" => $this->UsernameFromAccounts,
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                "status" => "error",
                "state" => false,
                "message" => $e->getMessage(),
                "data" => [],
            ]);
        }
    }
    public function changPassword(Request $request){
        try{
            $header = $request->header('Authorization');
            $jwt = $this->jwtUtils->verifyToken($header);
            if (!$jwt->state) return response()->json([
                "status" => "error",
                "state" => false,
                "message" => "Unauthorized",
                "data" => [],
            ], 401);

            $validators = Validator::make(
                $request -> all(),
                [
                    'old_password' => 'required | string',
                    'new_password' => 'required | string',
                    'confirm_password' => 'required | string'
                ]
            );

            if ($validators -> fails()) {
                return response()->json([
                    "status" => "error",
                    "state" => false,
                    "message" => "Bad request",
                    "data" => [
                        [
                            "validator" => $validators -> errors()
                        ]
                    ]
                        ], 400);
            }

            $oldPassword = $request->old_password;
            $newPassword = $request->new_password;
            $confirmPassword = $request->confirm_password;
            $username = $request->Username;
            // $username =  $this->name;

            $filter = ["Username" => $username];

            $result = $this->db->selectCollection("Accounts")->find($filter); //find likes a select, if add variable, it'll like a where
            $password = array();
            foreach ($result as $doc) \array_push($password, $doc);

            $isPass  = password_verify($oldPassword, $password[0]->Password);
            if (!$isPass){
                return response() -> json([
                    "status" => "error",
                    "state" => false,
                    "message" => "password for this user incorrect",
                    "data" => []
                ]);
            }

            if ($newPassword !== $confirmPassword){
                return response() -> json([
                    "status" => "error",
                    "state" => false,
                    "message" => "password is not match",
                    "data" => []
                ]);
            }

            $hash = $this->bcrypt->hash($newPassword);
            $update = ["Password" => $hash];
            $filter = ["Username" => $username];
            $result = $this->db->selectCollection("Accounts")->updateOne($filter, ['$set' => $update]);

            return response() -> json([
                "status" => "success",
                "state" => true,
                "message" => "change password successfully",
                "data" => []
            ]);


        }catch(\Exception $e){
            return response()->json([
                "status" => "error",
                "state" => false,
                "message" => $e->getMessage(),
                "data" => [],
            ]);
        }
    }

}
