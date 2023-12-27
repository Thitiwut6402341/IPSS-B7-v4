<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Http\Libraries\JWT\JWTUtils;
use App\Http\Libraries\Bcrypt;
use App\Models\FlexModel;
use Illuminate\Support\Facades\DB;

class iRPAController extends Controller
{
    private $mongo;
    private $db;
    private $jwtUtils;
    private $bcrypt;
    private $flexModel;

    public function __construct()
    {
        $mongo = new \MongoDB\Client("mongodb://iiot-center2:%24nc.ii0t%402o2E@10.0.0.7:27017/?authSource=admin");
        $this->db = $mongo->selectDatabase("IPSS_B7");
        $this->jwtUtils = new JWTUtils();
        $this->bcrypt = new Bcrypt(10);
        $this->flexModel = new FlexModel();
    }

    // RPA function
    public function RPA()
    {
        try {

            // JWT verification
            $header = request()->header('Authorization');
            $jwt = $this->jwtUtils->verifyToken($header);

            if (!$jwt->state) {
                return response()->json(['state' => false, 'msg' => 'Unauthorized API access'], 401);
            }

            $decoded = $jwt->decoded;

            $sql = "SELECT
            comName, premise, kWh, costAmountAfterVat,
            CAST(LEFT(p_period, 2) AS INT) AS [Month],
            CAST(RIGHT(p_period, 2) AS INT) +
            CASE WHEN YEAR(GETDATE()) + 543 >= 2600 THEN 2600 ELSE 2500 END - 543 AS [Year]
            FROM iRPA.dbo.V_ElectricityBills
            WHERE premise = '88/23';";

            $result = DB::select($sql);

            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json(['state' => false, 'msg' => $e->getMessage()]);
        }
    }
}
