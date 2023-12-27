<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Http\Libraries\JWT\JWTUtils;

class WeatherController extends Controller
{
    private $mongo;
    private $jwtUtils;

    private $db1;

    public function __construct()
    {
        $this->mongo = new \MongoDB\Client("mongodb://iiot-center2:%24nc.ii0t%402o2E@10.0.0.7:27017/?authSource=admin");
        $this->db1 = $this->mongo->selectDatabase("IPSS_B6");
        $this->jwtUtils = new JWTUtils();
    }

    // weather data
    public function weather(Request $request)
    {
        try {
            $header = $request->header('Authorization');
            $jwt = $this->jwtUtils->verifyToken($header);
            if (!$jwt->state) return response()->json([
                "status" => "error",
                "message" => "Unauthorized",
                "data" => [],
            ], 401);

            $result2 = $this->db1->selectCollection("Weather")->aggregate([


                [
                    '$project' => [
                        "_id" => 0,
                        "LogID" => [
                            '$toString' => '$_id'
                        ],
                        "Temp" => '$temp',
                        "Feel_like" => '$feel_like',
                        "Temp_max" => '$temp_max',
                        "Temp_min" => '$temp_min',
                        "Humidity" => '$humidity',
                        "Pressure" => '$pressure',
                        "Sea_level" => '$sea_level',
                        "Grnd_level" => '$grnd_level',
                        "Sunrise" => '$sunrise',
                        "Sunset" => '$sunset',
                        "Wind_Speed" => '$wind_Speed',
                        "Wind_deg" => '$wind_deg',
                        "Icon" => '$icon',
                        "Timestamp"  => [

                            '$dateToString' => [
                                'date' => '$Timestamp',
                                'format' => "%Y-%m-%d %H:%M:%S",
                            ],

                        ]
                    ]
                ],
                [
                    '$sort' => ["Timestamp" => -1]
                ],
                [
                    '$limit' => 1
                ],
            ]);
            $data = array();
            foreach ($result2 as $doc) array_push($data, $doc);

            return response()->json([
                "status" => "success",
                "message" => "Weather data",
                "data" =>  $data,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                "status" => "error",
                "message" => $e->getMessage(),
                "data" => [],
            ]);
        }
    }
}
