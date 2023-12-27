<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Http\Libraries\JWT\JWTUtils;


class WaterController extends Controller
{
    private $mongo;
    private $jwtUtils;

    private $db1;

    public function __construct()
    {
        $this->mongo = new \MongoDB\Client("mongodb://iiot-center2:%24nc.ii0t%402o2E@10.0.0.7:27017/?authSource=admin");
        $this->db1 = $this->mongo->selectDatabase("IPSS_B7");
        $this->jwtUtils = new JWTUtils();
    }
    public function wtDaily(Request $request)
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

            $Date = $request->date;
            $Shift = $request->shift;

            $dt = new \DateTime($Date);
            $date = $dt->format('Y-m-d');

            if (in_array(strtolower($Shift), ["day", "night"])) {
                $result2 = $this->db1->selectCollection("vwExpenseWTHourly")->aggregate([
                    [
                        '$match' => [
                            "Date" => "$date",
                            "Shift" => "$Shift"
                        ]
                    ],
                    [
                        '$project' => [
                            "AccUnit" => 1,
                            "Datetime" => 1,
                            "Time" => 1,
                            "Hour" => 1,
                            "Expenses" => 1,
                            "Shift" => 1,
                            "Target" => 1,
                        ]
                    ],
                    [
                        '$sort' => ["Datetime" => 1]
                    ],

                ]);
                $data = array();
                foreach ($result2 as $doc) array_push($data, $doc);

                return response()->json([
                    "status" => "success",
                    "state" => true,
                    "message" => "Use Water of Daily",
                    "data" => $data,
                ]);
            } else {

                $result2 = $this->db1->selectCollection("vwExpenseWTHourly")->aggregate([
                    [
                        '$match' => [
                            "Date" => "$date",
                        ]
                    ],
                    [
                        '$project' => [
                            "AccUnit" => 1,
                            "Datetime" => 1,
                            "Time" => 1,
                            "Hour" => 1,
                            "Expenses" => 1,
                            "Shift" => 1,
                            "Target" => 1,
                        ]
                    ],
                    [
                        '$sort' => ["Datetime" => 1]
                    ]
                ]);
                $data = array();
                foreach ($result2 as $doc) array_push($data, $doc);

                return response()->json([
                    "status" => "success",
                    "state" => true,
                    "message" => "Use water of Daily",
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

    public function wtMonthly(Request $request)
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
            $Date = $request->date;
            $Shift = $request->shift;
            $dt = new \DateTime($Date);
            $date = $dt->format('Y-m-d');
            $month = (int)$dt->format('m');
            if (in_array(strtolower($Shift), ["day", "night"])) {
                $result2 = $this->db1->selectCollection("vwExpenseWTHourly")->aggregate([
                    [
                        '$match' => [
                            "Month" => $month,
                            "Shift" => "$Shift"
                        ]
                    ],
                    [
                        '$group' => [
                            "_id" => [
                                // "AccUnit" => '$DB',
                                "Date" => '$Date',
                                "Shift" => '$Shift'
                            ],
                            "Expenses" => ['$sum' => '$Expenses'],
                            "AccUnit" => ['$sum' => '$AccUnit'],
                            "Target" => ['$min' => '$Target'],
                        ]
                    ],
                    [
                        '$project' => [
                            "_id" => 0,
                            "AccUnit" => '$AccUnit',
                            "Target" => ['$multiply' => ['$Target', 12]],
                            "Date" => '$_id.Date',
                            "Expenses" => 1,
                            "Shift" => '$_id.Shift'

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
                    "message" => "Use water of Monthly",
                    "data" => $data
                ]);
            } else {

                $result2 = $this->db1->selectCollection("vwExpenseWTHourly")->aggregate([
                    [
                        '$match' => [
                            "Month" => $month,
                        ]
                    ],
                    [
                        '$group' => [
                            "_id" => [
                                // "AccUnit" => '$DB',
                                "Date" => '$Date',
                            ],
                            "Expenses" => ['$sum' => '$Expenses'],
                            "Target" => ['$min' => '$Target'],
                            "AccUnit" => ['$sum' => '$AccUnit'],
                        ]
                    ],
                    [
                        '$project' => [
                            "_id" => 0,
                            "AccUnit" => 1,
                            "Target" => ['$multiply' => ['$Target', 24]],
                            "Date" => '$_id.Date',
                            "Expenses" => 1,
                            "Shift" => $Shift

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
                    "message" => "Use water of Monthly",
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

    public function wtYearly(Request $request)
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

            if (in_array(strtolower($Shift), ["day", "night"])) {
                $result2 = $this->db1->selectCollection("vwExpenseWTHourly")->aggregate([
                    [
                        '$match' => [
                            "Year" => $Year,
                            "Shift" => strtolower("$Shift")
                        ]
                    ],
                    [
                        '$group' => [
                            "_id" => [
                                // "AccUnit" => '$DB',
                                "YearMonth" => '$YearMonth',
                                "Year" => '$Year',
                                "Month" => '$Month',
                                "Shift" => '$Shift'
                            ],
                            "Expenses" => ['$sum' => '$Expenses'],
                            "AccUnit" => ['$sum' => '$AccUnit'],
                            "Target" => ['$min' => '$Target'],
                        ]
                    ],
                    [
                        '$project' => [
                            "_id" => 0,
                            "AccUnit" => 1,
                            "Target" => ['$multiply' => ['$Target', 12 * 30]],
                            "YearMonth" => '$_id.YearMonth',
                            "Year" => '$_id.Year',
                            "Month" => '$_id.Month',
                            "Expenses" => 1,
                            "Shift" => '$_id.Shift'

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
                    "message" => "Use Water of Yearly",
                    "data" => $data
                ]);
            } else {

                $result2 = $this->db1->selectCollection("vwExpenseWTHourly")->aggregate([
                    [
                        '$match' => [
                            "Year" => $Year,
                        ]
                    ],
                    [
                        '$group' => [
                            "_id" => [
                                // "AccUnit" => '$DB',
                                "YearMonth" => '$YearMonth',
                                "Year" => '$Year',
                                "Month" => '$Month',
                            ],
                            "Expenses" => ['$sum' => '$Expenses'],
                            "Target" => ['$min' => '$Target'],
                            "AccUnit" => ['$sum' => '$AccUnit'],
                        ]
                    ],
                    [
                        '$project' => [
                            "_id" => 0,
                            "AccUnit" => 1,
                            "Target" => ['$multiply' => ['$Target', 24 * 30]],
                            "YearMonth" => '$_id.YearMonth',
                            "Year" => '$_id.Year',
                            "Month" => '$_id.Month',
                            "Expenses" => 1,
                            "Shift" => $Shift

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
                    "message" => "Use Water of Yearly",
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

    public function wtLastXDay(Request $request)
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

            $dt = new \DateTime($Date);
            //   $year = (int)$dt->format('Y');
            //   $month = (int)$dt->format('m');
            $date = $dt->format('Y-m-d');


            $dateArray = array();
            array_push($dateArray, $date);

            for ($i = 1; $i < $DayCount; $i++) {
                array_push($dateArray, $dt->modify('-1 day')->format('Y-m-d'));
            }

            if (in_array(strtolower($shift), ["day", "night"])) {
                $result2 = $this->db1->selectCollection("vwExpenseWTHourly")->aggregate([
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
                                "Shift" => '$Shift'
                            ],
                            "Expenses" => ['$sum' => '$Expenses'],
                            "AccUnit" => ['$sum' => '$AccUnit'],
                            "Target" => ['$min' => '$Target'],

                        ]
                    ],
                    [
                        '$project' => [
                            "_id" => 0,
                            "Date" => '$_id.Date',
                            "Target" => ['$multiply' => ['$Target', 24]],
                            "Expenses" => 1,
                            "AccUnit" => 1,
                            "Shift" => '$_id.Shift'
                        ]
                    ],
                    [
                        '$sort' => ["Date" => 1]
                    ],
                ]);

                $data = array();
                foreach ($result2 as $doc) array_push($data, $doc);

                return response()->json([
                    "status" => "success",
                    "state" => true,
                    "message" => "Get to monitor water consumption last $DayCount day",
                    "data" =>  $data

                ]);
            } else {
                $result2 = $this->db1->selectCollection("vwExpenseWTHourly")->aggregate([
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
                            ],
                            "Expenses" => ['$sum' => '$Expenses'],
                            "AccUnit" => ['$sum' => '$AccUnit'],
                            "Target" => ['$min' => '$Target'],

                        ]
                    ],
                    [
                        '$project' => [
                            "_id" => 0,
                            "Date" => '$_id.Date',
                            "Target" => ['$multiply' => ['$Target', 24]],
                            "Expenses" => 1,
                            "AccUnit" => 1,
                            "Shift" => $shift,
                        ]
                    ],
                    [
                        '$sort' => ["Date" => 1]
                    ],
                ]);

                $data = array();
                foreach ($result2 as $doc) array_push($data, $doc);

                return response()->json([
                    "status" => "success",
                    "state" => true,
                    "message" => "Get to monitor water consumption last $DayCount day",
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

    // public function wt60Points(Request $request)
    // {
    //      try {
    //         $header = $request->header('Authorization');
    //         $jwt = $this->jwtUtils->verifyToken($header);
    //         if (!$jwt->state) return response()->json([
    //             "status" => "error",
    //             "state" => false,
    //             "message" => "Unauthorized",
    //             "data" => [],
    //         ], 401);

    //           $result2 = $this->db1->selectCollection("vwWaterMeter60Points")->aggregate([
    //                [
    //                     '$project' => [
    //                          "_id" => 0,
    //                          "Timestamp" => [

    //                                    '$dateToString' => [
    //                                      'date' => '$Timestamp',
    //                                      'format' => "%Y-%m-%d %H:%M:%S",
    //                                    ],
    //                          ],
    //                          "AccUnit" => 1,
    //                     ]
    //                ],
    //                [
    //                     '$sort' => ["Timestamp" => -1]
    //                ],
    //                [
    //                     '$limit' => 60
    //                ]

    //           ]);
    //           $data = array();
    //           foreach ($result2 as $doc) array_push($data, $doc);

    //           return response()->json([
    //             "status"=> "success",
    //             "state" => true,
    //             "message"=> "Get to monitor Historical 60 Points",
    //             "data"=> [
    //                 "Parameter" => $data,
    //             ]
    //         ]);



    //     } catch (\Exception $e) {
    //         return response()->json([
    //             "status" => "error",
    //             "state" => false,
    //             "message" => $e->getMessage(),
    //             "data" => [],
    //         ]);
    //     }
    // }

    // public function wt15Points(Request $request)
    // {
    //      try {
    //         $header = $request->header('Authorization');
    //         $jwt = $this->jwtUtils->verifyToken($header);
    //         if (!$jwt->state) return response()->json([
    //             "status" => "error",
    //             "state" => false,
    //             "message" => "Unauthorized",
    //             "data" => [],
    //         ], 401);

    //           $result2 = $this->db1->selectCollection("vwWaterMeter15Points")->aggregate([
    //                [
    //                     '$project' => [
    //                          "_id" => 0,
    //                          "Timestamp" => [

    //                                    '$dateToString' => [
    //                                      'date' => '$Timestamp',
    //                                      'format' => "%Y-%m-%d %H:%M:%S",
    //                                    ],
    //                          ],
    //                          "AccUnit" => 1,
    //                     ]
    //                ],
    //                [
    //                     '$sort' => ["Timestamp" => -1]
    //                ],
    //                [
    //                     '$limit' => 15
    //                ]

    //           ]);
    //           $data = array();
    //           foreach ($result2 as $doc) array_push($data, $doc);

    //           return response()->json([
    //             "status"=> "success",
    //             "state" => true,
    //             "message"=> "Get to monitor Historical 15 Points",
    //             "data"=> [
    //                 "Parameter" => $data,
    //             ]
    //         ]);


    //     } catch (\Exception $e) {
    //         return response()->json([
    //             "status" => "error",
    //             "state" => false,
    //             "message" => $e->getMessage(),
    //             "data" => [],
    //         ]);
    //     }
    // }

    public function wtAllTarget(Request $request)
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

            $result2 = $this->db1->selectCollection("vwAllWTTarget")->aggregate([
                [
                    '$project' => [
                        "_id" => 0,
                        "Date" => 1,
                        "Target" => '$Target',
                    ]
                ],
                [
                    '$sort' => ["Date" => -1]
                ]

            ]);
            $data = array();
            foreach ($result2 as $doc) array_push($data, $doc);

            return response()->json([
                "status" => "success",
                "state" => true,
                "message" => "Get all to monitor Water Target",
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

    // Water unit usage for x mins
    public function wtUnitPastXPoints(Request $request)
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
                            "Minutes" => $validators->errors()
                        ]
                    ]
                ], 400);
            }

            $points = $request->points;

            $result2 = $this->db1->selectCollection("WaterMeter")->aggregate([
                [
                    '$sort' => [
                        "AccUnit" => 1,
                        "Timestamp" => 1
                    ]
                ],
                [
                    '$group' => [
                        "_id" => [
                            "AccUnit" => '$AccUnit',
                        ],
                        "Timestamp" => ['$min' => '$Timestamp'],
                    ]
                ],
                [
                    '$project' => [
                        '_id' => 0,
                        'AccUnit' => '$_id.AccUnit',
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
                        'AccUnit' => 1,
                        'Timestamp' => [
                            '$dateToString' => ['date' => '$Timestamp', 'format' => '%Y-%m-%d %H:%M']
                        ]
                    ]
                ],
                [
                    '$limit' => $points
                ]

            ]);

            $data = array();
            foreach ($result2 as $doc) array_push($data, $doc);

            return response()->json([
                "status" => "success",
                "state" => true,
                "message" => "Water unit for $points points",
                "data" => $data,
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
