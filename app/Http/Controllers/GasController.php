<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Http\Libraries\JWT\JWTUtils;


class GasController extends Controller
{
    private $mongo;
    private $jwtUtils;

    private $db;

    public function __construct()
    {
        $this->mongo = new \MongoDB\Client("mongodb://iiot-center2:%24nc.ii0t%402o2E@10.0.0.7:27017/?authSource=admin");
        $this->db = $this->mongo->selectDatabase("IPSS_B7");
        $this->jwtUtils = new JWTUtils();
    }

    // gas usage per day
    public function gasDaily(Request $request)
    {
        try {

            $header = $request->header('Authorization');
            $jwt = $this->jwtUtils->verifyToken($header);
            if (!$jwt->state) return response()->json([
                "status" => "error",
                "state" => false,
                "message" => "Unauthorized",
                "data" => [],
            ], 401);

            $validators = Validator::make(
                $request->all(),
                [
                    'GasMeter' => 'required|string',
                    'date' => 'required |string',
                    'shift' => 'required|string '
                ]
            );

            if ($validators->fails()) {
                return response()->json([
                    "status" => "error",
                    "message" => "กรอกข้อมูลไม่ครบถ้วน",
                    "data" => [
                        [
                            "validators" => $validators->errors()
                        ]
                    ]
                ], 400);
            }

            $gasMeter = $request->GasMeter;
            $date = $request->date;
            $shift = $request->shift;

            $allGas = ['LPG', 'Nitrogen', 'Oxygen'];
            if (!in_array($gasMeter, $allGas, true)) {
                return response()->json([
                    "status" => "error",
                    "state" => false,
                    "message" => "ไม่มี Gas นี้ในระบบ",
                ], 400);
            }

            if (in_array(strtolower($shift), ["day", "night"])) {

                $filter = [
                    "GasMeter" => $gasMeter,
                    "Shift" => $shift,
                    "Date" => $date
                ];

                $results = $this->db->selectCollection("vw" . $gasMeter . "ConsumptionHourly")->find($filter);

                $data = array();
                foreach ($results as $doc)
                    array_push($data, $doc);

                return response()->json([
                    "status" => "success",
                    "state" => true,
                    "message" => "Gas usage per day (each hour)",
                    "data" => $data,
                ]);
            } else {

                $filter = [
                    "GasMeter" => $gasMeter,
                    "Date" => $date
                ];

                $results = $this->db->selectCollection("vw" . $gasMeter . "ConsumptionHourly")->find($filter);

                $data = array();
                foreach ($results as $doc)
                    array_push($data, $doc);

                return response()->json([
                    "status" => "success",
                    "state" => true,
                    "message" => "Gas usage per day (each hour)",
                    "data" => $data,
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                "status" => "error",
                "state" => false,
                "message" => $e->getMessage(),
                "data" => [],
            ]);
        }
    }

    // Gas usage per month
    public function gasMonthly(Request $request)
    {
        try {
            $header = $request->header('Authorization');
            $jwt = $this->jwtUtils->verifyToken($header);
            if (!$jwt->state) return response()->json([
                "status" => "error",
                "state" => false,
                "message" => "Unauthorized",
                "data" => [],
            ], 401);

            $validators = Validator::make(
                $request->all(),
                [
                    'GasMeter' => 'required |string',
                    'date' => 'required |string',
                    'shift' => 'required|string '

                ]
            );

            if ($validators->fails()) {
                return response()->json([
                    "status" => "error",
                    "state" => false,
                    "message" => "กรอกข้อมูลไม่ครบถ้วน",
                    "data" => [
                        [
                            "validators" => $validators->errors()
                        ]
                    ]
                ], 400);
            }

            $gasMeter = $request->GasMeter;
            $date = $request->date;
            $shift = $request->shift;
            $dt = new \DateTime($date);
            $date = $dt->format('Y-m-d');
            $month = (int)$dt->format('m');

            $allGas = ['LPG', 'Nitrogen', 'Oxygen'];
            if (!in_array($gasMeter, $allGas, true)) {
                return response()->json([
                    "status" => "error",
                    "state" => false,
                    "message" => "ไม่มี Gas นี้ในระบบ",
                ], 400);
            }

            if (in_array(strtolower($shift), ["day", "night"])) {
                $result2 = $this->db->selectCollection("vw" . $gasMeter . "ConsumptionHourly")->aggregate([
                    [
                        '$match' => [
                            "GasMeter" => $gasMeter,
                            "Month" => $month,
                            "Shift" => $shift,
                        ]
                    ],
                    [
                        '$group' => [
                            "_id" => [
                                // "AccUnit" => '$DB',
                                "Date" => '$Date',
                                "Shift" => '$Shift',
                                // "Target" => '$Target',
                            ],
                            "Expenses" => ['$sum' => '$Expenses'],
                            "UnitGas" => ['$sum' => '$UnitGas'],
                            "Target" => ['$min' => '$Target'],
                            // "Target" => ['$multiply'=>[3,2]],
                        ]
                    ],
                    [
                        '$project' => [
                            "_id" => 0,
                            "UnitGas" => 1,
                            "Target" => ['$multiply' => ['$Target', 12]],
                            "Date" => '$_id.Date',
                            "Expenses" => 1,
                            // "Target" => '$_id.Target',
                            "Shift" =>'$_id.Shift',
                        ]
                    ],
                    [
                        '$sort' => ["Date" => 1]
                    ]

                ]);
                $data = array();
                foreach ($result2 as $doc) array_push($data, $doc);

                return response()->json([
                    "status" => "success",
                    "state" => true,
                    "message" => "Use gas of Monthly",
                    "data" => $data
                ]);
            } else {

                $result2 = $this->db->selectCollection("vw" . $gasMeter . "ConsumptionHourly")->aggregate([
                    [
                        '$match' => [
                            "GasMeter" => $gasMeter,
                            "Month" => $month,
                        ]
                    ],
                    [
                        '$group' => [
                            "_id" => [
                                // "AccUnit" => '$DB',
                                "Date" => '$Date',
                                // "Shift" => '$Shift',
                                // "Target" => '$Target',
                            ],
                            "Expenses" => ['$sum' => '$Expenses'],
                            "Target" => ['$min' => '$Target'],
                            "UnitGas" => ['$sum' => '$UnitGas'],
                        ]
                    ],
                    [
                        '$project' => [
                            "_id" => 0,
                            "UnitGas" => 1,
                            "Target" => ['$multiply' => ['$Target', 24]],
                            "Date" => '$_id.Date',
                            "Expenses" => 1,
                            // "Target" => '$_id.Target',
                            "Shift" => $shift
                        ]
                    ],
                    [
                        '$sort' => ["Date" => 1]
                    ]
                ]);
                $data = array();
                foreach ($result2 as $doc) array_push($data, $doc);

                return response()->json([
                    "status" => "success",
                    "state" => true,
                    "message" => "Use gas of Monthly",
                    "data" => $data
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                "status" => "error",
                "state" => false,
                "message" => $e->getMessage(),
                "data" => [],
            ]);
        }
    }

    public function gasYearly(Request $request)
    {
        try {
            $header = $request->header('Authorization');
            $jwt = $this->jwtUtils->verifyToken($header);
            if (!$jwt->state) return response()->json([
                "status" => "error",
                "state" => false,
                "message" => "Unauthorized",
                "data" => [],
            ], 401);

            $validators = Validator::make(
                $request->all(),
                [
                    'GasMeter' => 'required|string ',
                    'year' => 'required |int',
                    'shift' => 'required|string '

                ]
            );

            if ($validators->fails()) {
                return response()->json([
                    "status" => "error",
                    "state" => false,
                    "message" => "กรอกข้อมูลไม่ครบถ้วน",
                    "data" => [
                        [
                            "validators" => $validators->errors()
                        ]
                    ]
                ], 400);
            }
            $Year = $request->year;
            $Shift = $request->shift;
            $gasMeter = $request->GasMeter;

            $allGas = ['LPG', 'Nitrogen', 'Oxygen'];
            if (!in_array($gasMeter, $allGas, true)) {
                return response()->json([
                    "status" => "error",
                    "state" => false,
                    "message" => "ไม่มี Gas นี้ในระบบ",
                ], 400);
            }

            if (in_array(strtolower($Shift), ["day", "night"])) {
                $result2 = $this->db->selectCollection("vw" . $gasMeter . "ConsumptionHourly")->aggregate([
                    [
                        '$match' => [
                            "Year" => $Year,
                            "Shift" => strtolower("$Shift"),
                            "GasMeter" => $gasMeter,

                        ]
                    ],
                    [
                        '$group' => [
                            "_id" => [
                                "GasMeter" => '$GasMeter',
                                "YearMonth" => '$YearMonth',
                                "Year" => '$Year',
                                "Month" => '$Month',
                                "Shift" => '$Shift'
                            ],
                            "Expenses" => ['$sum' => '$Expenses'],
                            "UnitGas" => ['$sum' => '$UnitGas'],
                            "Target" => ['$min' => '$Target'],
                        ]
                    ],
                    [
                        '$project' => [
                            "_id" => 0,
                            "UnitGas" => 1,
                            "Target" => ['$multiply' => ['$Target', 12 * 30]],
                            "YearMonth" => '$_id.YearMonth',
                            "Year" => '$_id.Year',
                            "Month" => '$_id.Month',
                            "Expenses" => 1,
                            "Shift" => '$_id.Shift',
                            "GasMeter" => '$_id.GasMeter',

                        ]
                    ],
                    [
                        '$sort' => ["Month" => 1]
                    ]

                ]);
                $data = array();
                foreach ($result2 as $doc) array_push($data, $doc);

                return response()->json([
                    "status" => "success",
                    "state" => true,
                    "message" => "Use Gas of Yearly",
                    "data" => $data
                ]);
            } else {

                $result2 = $this->db->selectCollection("vw" . $gasMeter . "ConsumptionHourly")->aggregate([
                    [
                        '$match' => [
                            "Year" => $Year,
                            "GasMeter" => $gasMeter,
                        ]
                    ],
                    [
                        '$group' => [
                            "_id" => [
                                "GasMeter" => '$GasMeter',
                                "YearMonth" => '$YearMonth',
                                "Year" => '$Year',
                                "Month" => '$Month',
                                // "Shift" => '$Shift'
                            ],
                            "Expenses" => ['$sum' => '$Expenses'],
                            "Target" => ['$min' => '$Target'],
                            "UnitGas" => ['$sum' => '$UnitGas'],
                        ]
                    ],
                    [
                        '$project' => [
                            "_id" => 0,
                            "UnitGas" => 1,
                            "Target" => ['$multiply' => ['$Target', 24 * 30]],
                            "YearMonth" => '$_id.YearMonth',
                            "Year" => '$_id.Year',
                            "Month" => '$_id.Month',
                            "Expenses" => 1,
                            "Shift" => $Shift,
                            "GasMeter" => '$_id.GasMeter',

                        ]
                    ],
                    [
                        '$sort' => ["Month" => 1]
                    ]
                ]);
                $data = array();
                foreach ($result2 as $doc) array_push($data, $doc);

                return response()->json([
                    "status" => "success",
                    "state" => true,
                    "message" => "Use Gas of Yearly",
                    "data" => $data
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                "status" => "error",
                "state" => false,
                "message" => $e->getMessage(),
                "data" => [],
            ]);
        }
    }

    // Monitor historical data xxx days
    public function gasLastXDay(Request $request)
    {
        try {
            $header = $request->header('Authorization');
            $jwt = $this->jwtUtils->verifyToken($header);
            if (!$jwt->state) return response()->json([
                "status" => "error",
                "state" => false,
                "message" => "Unauthorized",
                "data" => [],
            ], 401);

            $validators = Validator::make(
                $request->all(),
                [
                    'GasMeter' => 'required | string',
                    'date' => 'required |string',
                    'daycount' => 'required|int ',
                    'shift' => 'required|string '

                ]
            );

            if ($validators->fails()) {
                return response()->json([
                    "status" => "error",
                    "state" => false,
                    "message" => "กรอกข้อมูลไม่ครบถ้วน",
                    "data" => [
                        [
                            "validators" => $validators->errors()
                        ]
                    ]
                ], 400);
            }

            $Date = $request->date;
            $DayCount = $request->daycount;
            $shift = $request->shift;
            $gasMeter = $request->GasMeter;

            $allGas = ['LPG',  'Nitrogen', 'Oxygen'];
            if (!in_array($gasMeter, $allGas, true)) {
                return response()->json([
                    "status" => "error",
                    "state" => false,
                    "message" => "ไม่มี Gas นี้ในระบบ",
                ], 400);
            }

            $dt = new \DateTime($Date);
            // $year = (int)$dt->format('Y');
            // $month = (int)$dt->format('m');
            $date = $dt->format('Y-m-d');


            $dateArray = array();
            array_push($dateArray, $date);

            for ($i = 1; $i < $DayCount; $i++) {
                array_push($dateArray, $dt->modify('-1 day')->format('Y-m-d'));
            }

            if (in_array(strtolower($shift), ["day", "night"])) {
                $result2 = $this->db->selectCollection("vw" . $gasMeter . "ConsumptionHourly")->aggregate([
                    [
                        '$match' => [
                            "Shift" => $shift,
                            "Date" => [
                                '$in' =>
                                $dateArray
                            ],
                        ]
                    ],
                    [
                        '$group' => [
                            "_id" => [
                                "Date" => '$Date',
                                "Shift" => '$Shift',
                                "GasMeter" => '$GasMeter',
                            ],
                            "Expenses" => ['$sum' => '$Expenses'],
                            "UnitGas" => ['$sum' => '$UnitGas'],
                            "Target" => ['$min' => '$Target'],

                        ]
                    ],
                    [
                        '$project' => [
                            "_id" => 0,
                            "Date" => '$_id.Date',
                            "Target" => ['$multiply' => ['$Target', 24]],
                            "Shift" => '$_id.Shift',
                            "GasMeter" => '$_id.GasMeter',
                            "Expenses" => 1,
                            "UnitGas" => 1,
                        ]
                    ],
                    [
                        '$sort' => ["Date" => 1]
                    ],
                    // [
                    //      '$sort' => ["Date" => -1]
                    // ]
                ]);
                $data = array();
                foreach ($result2 as $doc) array_push($data, $doc);

                return response()->json([
                    "status" => "success",
                    "state" => true,
                    "message" => "Historical data for $DayCount days ago",
                    "data" =>  $data,
                ]);
            } else {
                $result2 = $this->db->selectCollection("vw" . $gasMeter . "ConsumptionHourly")->aggregate([
                    [
                        '$match' => [
                            "Date" => [
                                '$in' =>
                                $dateArray
                            ],
                        ]
                    ],
                    [
                        '$group' => [
                            "_id" => [
                                "Date" => '$Date',
                                // "Shift" => '$Shift',
                                "GasMeter" => '$GasMeter',
                            ],
                            "Expenses" => ['$sum' => '$Expenses'],
                            "UnitGas" => ['$sum' => '$UnitGas'],
                            "Target" => ['$min' => '$Target'],

                        ]
                    ],
                    [
                        '$project' => [
                            "_id" => 0,
                            "Date" => '$_id.Date',
                            "Target" => ['$multiply' => ['$Target', 24]],
                            "Shift" => $shift,
                            "GasMeter" => '$_id.GasMeter',
                            "Expenses" => 1,
                            "UnitGas" => 1,
                        ]
                    ],
                    [
                        '$sort' => ["Date" => 1]
                    ],
                    // [
                    //      '$sort' => ["Date" => -1]
                    // ]
                ]);
                $data = array();
                foreach ($result2 as $doc) array_push($data, $doc);

                return response()->json([
                    "status" => "success",
                    "state" => true,
                    "message" => "Historical data for $DayCount days ago",
                    "data" =>  $data,
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                "status" => "error",
                "state" => false,
                "message" => $e->getMessage(),
                "data" => [],
            ]);
        }
    }

    public function gasAllTarget(Request $request)
    {
        try {
            $header = $request->header('Authorization');
            $jwt = $this->jwtUtils->verifyToken($header);
            if (!$jwt->state) return response()->json([
                "status" => "error",
                "state" => false,
                "message" => "Unauthorized",
                "data" => [],
            ], 401);

            $result2 = $this->db->selectCollection("vwGasAllTarget")->aggregate([
                [
                    '$project' => [
                        "_id" => 0,
                        "Date" => 1,
                        "LPG" => 1,
                        "Nitrogen" => 1,
                        "Oxygen" => 1,
                    ]
                ],
                [
                    '$sort' => ["date" => -1]
                ]

            ]);
            $data = array();
            foreach ($result2 as $doc) array_push($data, $doc);

            return response()->json([
                "status" => "success",
                "state" => true,
                "message" => "Get all to monitor Target",
                "data" => $data
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

    public function gasFlowrateUnitPastXPoints(Request $request)
    {
        try {
            $header = $request->header('Authorization');
            $jwt = $this->jwtUtils->verifyToken($header);
            if (!$jwt->state) return response()->json([
                "status" => "error",
                "state" => false,
                "message" => "Unauthorized",
                "data" => [],
            ], 401);

            $validators = Validator::make(
                $request->all(),
                [
                    'points' => 'required|int',
                ]
            );

            if ($validators->fails()) {
                return response()->json([
                    "status" => "error",
                    "state" => false,
                    "message" => "กรอกข้อมูลไม่ครบถ้วน",
                    "data" => [
                        [
                            "Points" => $validators->errors()
                        ]
                    ]
                ], 400);
            }

            $points = $request->points;

            $resultLPG = $this->db->selectCollection("GasLPG")->aggregate([
                [
                    '$sort' => [
                        "FlowRate" => 1,
                        "Timestamp" => 1
                    ]
                ],
                [
                    '$group' => [
                        "_id" => [

                            "FlowRate" => '$FlowRate',
                        ],
                        "Timestamp" => ['$min' => '$Timestamp'],
                    ]
                ],
                [
                    '$project' => [
                        '_id' => 0,
                        'FlowRate' => '$_id.FlowRate',
                        'Timestamp' => '$Timestamp',

                    ]
                ],
                [
                    '$sort' => [
                        "Timestamp" => -1
                    ]
                ],
                [
                    '$project' => [
                        'FlowRate' => 1,
                        'GasMeter' => 'LPG',
                        'Timestamp' => [
                            '$dateToString' => ['date' => '$Timestamp', 'format' => '%Y-%m-%d %H:%M:%S']
                        ]
                    ]
                ],
                [
                    '$limit' => $points
                ]

            ]);


            $resultNitrogen = $this->db->selectCollection("GasNitrogen")->aggregate([
                [
                    '$sort' => [
                        "FlowRate" => 1,
                        "Timestamp" => 1
                    ]
                ],
                [
                    '$group' => [
                        "_id" => [
                            "FlowRate" => '$FlowRate',
                        ],
                        "Timestamp" => ['$min' => '$Timestamp'],
                    ]
                ],
                [
                    '$project' => [
                        '_id' => 0,
                        'FlowRate' => '$_id.FlowRate',
                        'Timestamp' => '$Timestamp',
                    ]
                ],
                [
                    '$sort' => [
                        "Timestamp" => -1
                    ]
                ],
                [
                    '$project' => [
                        'FlowRate' => 1,
                        'GasMeter' => 'Nitrogen',
                        'Timestamp' => [
                            '$dateToString' => ['date' => '$Timestamp', 'format' => '%Y-%m-%d %H:%M:%S']
                        ]
                    ]
                ],
                [
                    '$limit' => $points
                ]

            ]);

            $resultOxygen = $this->db->selectCollection("GasOxygen")->aggregate([
                [
                    '$sort' => [
                        "FlowRate" => 1,
                        "Timestamp" => 1
                    ]
                ],
                [
                    '$group' => [
                        "_id" => [
                            "FlowRate" => '$FlowRate',
                        ],
                        "Timestamp" => ['$min' => '$Timestamp'],
                    ]
                ],
                [
                    '$project' => [
                        '_id' => 0,
                        'FlowRate' => '$_id.FlowRate',
                        'Timestamp' => '$Timestamp',
                    ]
                ],
                [
                    '$sort' => [
                        "Timestamp" => -1
                    ]
                ],
                [
                    '$project' => [
                        'FlowRate' => 1,
                        'GasMeter' => 'Oxygen',
                        'Timestamp' => [
                            '$dateToString' => ['date' => '$Timestamp', 'format' => '%Y-%m-%d %H:%M:%S']
                        ]
                    ]
                ],
                [
                    '$limit' => $points
                ]

            ]);


            $data = array();

            foreach ($resultLPG as $doc) array_push($data, $doc);
            foreach ($resultNitrogen as $doc) array_push($data, $doc);
            foreach ($resultOxygen as $doc) array_push($data, $doc);

            return response()->json([
                "status" => "success",
                "state" => true,
                "message" => "Gas unit for $points points",
                "data" => $data


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



    public function gasVelocityPastXPoints(Request $request)
    {
        try {
            $header = $request->header('Authorization');
            $jwt = $this->jwtUtils->verifyToken($header);
            if (!$jwt->state) return response()->json([
                "status" => "error",
                "state" => false,
                "message" => "Unauthorized",
                "data" => [],
            ], 401);

            $validators = Validator::make(
                $request->all(),
                [
                    'points' => 'required|int',
                ]
            );

            if ($validators->fails()) {
                return response()->json([
                    "status" => "error",
                    "state" => false,
                    "message" => "กรอกข้อมูลไม่ครบถ้วน",
                    "data" => [
                        [
                            "Points" => $validators->errors()
                        ]
                    ]
                ], 400);
            }

            $points = $request->points;

            $resultLPG = $this->db->selectCollection("GasLPG")->aggregate([
                [
                    '$sort' => [
                        "Velocity" => 1,
                        "Timestamp" => 1
                    ]
                ],
                [
                    '$group' => [
                        "_id" => [

                            "Velocity" => '$Velocity',
                        ],
                        "Timestamp" => ['$min' => '$Timestamp'],
                    ]
                ],
                [
                    '$project' => [
                        '_id' => 0,
                        'Velocity' => '$_id.Velocity',
                        'Timestamp' => '$Timestamp',

                    ]
                ],
                [
                    '$sort' => [
                        "Timestamp" => -1
                    ]
                ],
                [
                    '$project' => [
                        'Velocity' => 1,
                        'GasMeter' => 'LPG',
                        'Timestamp' => [
                            '$dateToString' => ['date' => '$Timestamp', 'format' => '%Y-%m-%d %H:%M:%S']
                        ]
                    ]
                ],
                [
                    '$limit' => $points
                ]

            ]);


            $resultNitrogen = $this->db->selectCollection("GasNitrogen")->aggregate([
                [
                    '$sort' => [
                        "Velocity" => 1,
                        "Timestamp" => 1
                    ]
                ],
                [
                    '$group' => [
                        "_id" => [
                            "Velocity" => '$Velocity',
                        ],
                        "Timestamp" => ['$min' => '$Timestamp'],
                    ]
                ],
                [
                    '$project' => [
                        '_id' => 0,
                        'Velocity' => '$_id.Velocity',
                        'Timestamp' => '$Timestamp',
                    ]
                ],
                [
                    '$sort' => [
                        "Timestamp" => -1
                    ]
                ],
                [
                    '$project' => [
                        'Velocity' => 1,
                        'GasMeter' => 'Nitrogen',
                        'Timestamp' => [
                            '$dateToString' => ['date' => '$Timestamp', 'format' => '%Y-%m-%d %H:%M:%S']
                        ]
                    ]
                ],
                [
                    '$limit' => $points
                ]

            ]);

            $resultOxygen = $this->db->selectCollection("GasOxygen")->aggregate([
                [
                    '$sort' => [
                        "Velocity" => 1,
                        "Timestamp" => 1
                    ]
                ],
                [
                    '$group' => [
                        "_id" => [
                            "Velocity" => '$Velocity',
                        ],
                        "Timestamp" => ['$min' => '$Timestamp'],
                    ]
                ],
                [
                    '$project' => [
                        '_id' => 0,
                        'Velocity' => '$_id.Velocity',
                        'Timestamp' => '$Timestamp',
                    ]
                ],
                [
                    '$sort' => [
                        "Timestamp" => -1
                    ]
                ],
                [
                    '$project' => [
                        'Velocity' => 1,
                        'GasMeter' => 'Oxygen',
                        'Timestamp' => [
                            '$dateToString' => ['date' => '$Timestamp', 'format' => '%Y-%m-%d %H:%M:%S']
                        ]
                    ]
                ],
                [
                    '$limit' => $points
                ]

            ]);


            $data = array();

            foreach ($resultLPG as $doc) array_push($data, $doc);
            foreach ($resultNitrogen as $doc) array_push($data, $doc);
            foreach ($resultOxygen as $doc) array_push($data, $doc);

            return response()->json([
                "status" => "success",
                "state" => true,
                "message" => "Gas unit for $points points",
                "data" => $data


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


    public function gasCumulativePastXPoints(Request $request)
    {
        try {
            $header = $request->header('Authorization');
            $jwt = $this->jwtUtils->verifyToken($header);
            if (!$jwt->state) return response()->json([
                "status" => "error",
                "state" => false,
                "message" => "Unauthorized",
                "data" => [],
            ], 401);

            $validators = Validator::make(
                $request->all(),
                [
                    'points' => 'required|int',
                ]
            );

            if ($validators->fails()) {
                return response()->json([
                    "status" => "error",
                    "state" => false,
                    "message" => "กรอกข้อมูลไม่ครบถ้วน",
                    "data" => [
                        [
                            "Points" => $validators->errors()
                        ]
                    ]
                ], 400);
            }

            $points = $request->points;

            $resultLPG = $this->db->selectCollection("GasLPG")->aggregate([
                [
                    '$sort' => [
                        "Cumulative" => 1,
                        "Timestamp" => 1
                    ]
                ],
                [
                    '$group' => [
                        "_id" => [

                            "Cumulative" => '$Cumulative',
                        ],
                        "Timestamp" => ['$min' => '$Timestamp'],
                    ]
                ],
                [
                    '$project' => [
                        '_id' => 0,
                        'Cumulative' => '$_id.Cumulative',
                        'Timestamp' => '$Timestamp',

                    ]
                ],
                [
                    '$sort' => [
                        "Timestamp" => -1
                    ]
                ],
                [
                    '$project' => [
                        'Cumulative' => 1,
                        'GasMeter' => 'LPG',
                        'Timestamp' => [
                            '$dateToString' => ['date' => '$Timestamp', 'format' => '%Y-%m-%d %H:%M:%S']
                        ]
                    ]
                ],
                [
                    '$limit' => $points
                ]

            ]);


            $resultNitrogen = $this->db->selectCollection("GasNitrogen")->aggregate([
                [
                    '$sort' => [
                        "Cumulative" => 1,
                        "Timestamp" => 1
                    ]
                ],
                [
                    '$group' => [
                        "_id" => [
                            "Cumulative" => '$Cumulative',
                        ],
                        "Timestamp" => ['$min' => '$Timestamp'],
                    ]
                ],
                [
                    '$project' => [
                        '_id' => 0,
                        'Cumulative' => '$_id.Cumulative',
                        'Timestamp' => '$Timestamp',
                    ]
                ],
                [
                    '$sort' => [
                        "Timestamp" => -1
                    ]
                ],
                [
                    '$project' => [
                        'Cumulative' => 1,
                        'GasMeter' => 'Nitrogen',
                        'Timestamp' => [
                            '$dateToString' => ['date' => '$Timestamp', 'format' => '%Y-%m-%d %H:%M:%S']
                        ]
                    ]
                ],
                [
                    '$limit' => $points
                ]

            ]);

            $resultOxygen = $this->db->selectCollection("GasOxygen")->aggregate([
                [
                    '$sort' => [
                        "Cumulative" => 1,
                        "Timestamp" => 1
                    ]
                ],
                [
                    '$group' => [
                        "_id" => [
                            "Cumulative" => '$Cumulative',
                        ],
                        "Timestamp" => ['$min' => '$Timestamp'],
                    ]
                ],
                [
                    '$project' => [
                        '_id' => 0,
                        'Cumulative' => '$_id.Cumulative',
                        'Timestamp' => '$Timestamp',
                    ]
                ],
                [
                    '$sort' => [
                        "Timestamp" => -1
                    ]
                ],
                [
                    '$project' => [
                        'Cumulative' => 1,
                        'GasMeter' => 'Oxygen',
                        'Timestamp' => [
                            '$dateToString' => ['date' => '$Timestamp', 'format' => '%Y-%m-%d %H:%M:%S']
                        ]
                    ]
                ],
                [
                    '$limit' => $points
                ]

            ]);


            $data = array();

            foreach ($resultLPG as $doc) array_push($data, $doc);
            foreach ($resultNitrogen as $doc) array_push($data, $doc);
            foreach ($resultOxygen as $doc) array_push($data, $doc);

            return response()->json([
                "status" => "success",
                "state" => true,
                "message" => "Gas unit for $points points",
                "data" => $data


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
}
