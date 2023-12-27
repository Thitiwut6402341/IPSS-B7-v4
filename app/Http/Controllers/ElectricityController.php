<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Http\Libraries\JWT\JWTUtils;


class ElectricityController extends Controller
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

    // Login function
    public function ecDaily(Request $request)
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
                $request -> all(),
                [
                    'DB' => 'required|string',
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

            $DB = $request->DB;
            $Date = $request->date;
            $Shift = $request->shift;

            $allDB = ['MDB'];
            if (!in_array($DB, $allDB, true)) {
                return response()->json([
                    "status" => "error",
                    "state" => false,
                    "message" => "ไม่มี DB นี้ในระบบ",
                ],400);
            }


            if(in_array(strtolower($Shift),["day","night"])){

                $filter = [    "DB" => $DB,
                               "Shift" => $Shift,
                               "Date" => $Date ];
                $options = [   "sort" =>[
                               "Datetime" => -1],
                               "projection" => [
                               "DB"=> 1,
                               "kWh"=> 1,
                               "Target"=> 1,
                               "Datetime"=> 1,
                               "Time"=> 1,
                               "Hour"=> 1,
                               "Expenses"=> 1,
                               "Shift" => 1,
                                    ]];

                $results = $this->db1->selectCollection("vwPowerConsumption".$DB."Hourly")->find($filter,$options);

                $data = array();
                foreach ($results as $doc)
                array_push($data, $doc);

                return response()->json([
                        "status"=> "success",
                        "state" => true,
                        "message"=> "Get to view electricity in day count ago",
                        "data"=> $data,
                    ]);


               }else{

                    $filter = [    "DB" => $DB,
                                   "Date" => $Date ];
                    $options = [   "sort" =>[
                                   "Datetime" => -1],
                                   "projection" => [
                                   "DB"=> 1,
                                   "kWh"=> 1,
                                   "Target"=> 1,
                                   "Datetime"=> 1,
                                   "Time"=> 1,
                                   "Hour"=> 1,
                                   "Expenses"=> 1,
                                   "Shift" => 1,
                                        ]];

                $results = $this->db1->selectCollection("vwPowerConsumption".$DB."Hourly")->find($filter,$options);

                $data = array();
                foreach ($results as $doc)
                array_push($data, $doc);

                return response()->json([
                        "status"=> "success",
                        "state" => true,
                        "message"=> "Use electricity of Daily",
                        "data"=> $data,

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

    public function ecExpensesDaily(Request $request)
     {
          try {
            $header = $request->header('Authorization');
            $jwt = $this->jwtUtils->verifyToken($header);
            if (!$jwt->state) return response()->json([
                "status" => "error",
                "message" => "Unauthorized",
                "data" => [],
            ], 401);

            $validators = Validator::make(
                $request -> all(),
                [
                    'DB' => 'required|string',
                    'date' => 'required |string',
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

            $DB = $request->DB;
            $Date = $request->date;

            $allDB = ['MDB'];
            if (!in_array($DB, $allDB, true)) {
                return response()->json([
                    "status" => "error",
                    "state" => false,
                    "message" => "ไม่มี DB นี้ในระบบ",
                ],400);
            }


                $result2 = $this->db1->selectCollection("vwPowerConsumption".$DB."Hourly")->aggregate([
                        [
                            '$match' => [
                                "Date" => $Date,
                            ]
                        ],
                        [
                            '$group' => [
                                "_id" => [
                                    "DB" => '$DB',
                                    "Date" => '$Date',
                                    "timeType" => '$TimeType'
                                ],
                                "Expenses" => ['$sum' => '$Expenses'],
                                "kWh" => ['$sum' => '$kWh'],
                            ]
                        ],
                        [
                            '$project' => [
                                "_id" => 0,
                                "DB" => '$_id.DB',
                                "Date" => '$_id.Date',
                                "timeType" => '$_id.timeType',
                                "Expenses" => 1,
                                "kWh" => 1,
                            ]
                        ],
                        [
                            '$project' => [
                                "_id" => 0,
                            ]
                            ],
                        [
                            '$sort' => ["Date" => -1]
                        ]
                ]);
                $data = array();
                foreach ($result2 as $doc) array_push($data, $doc);

                return response()->json([
                    "status"=> "success",
                    "state" => true,
                    "message"=> "Expensive electricity of Daily",
                    "data"=> $data
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

     public function ecMonthly(Request $request)
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
                $request -> all(),
                [
                    'DB' => 'required|string',
                    'date' => 'required |string',
                    'shift' => 'required |string',
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

            $DB = $request->DB;
            $Date = $request->date;
            $Shift= $request->shift;
            $allDB = ['MDB'];
            if (!in_array($DB, $allDB, true)) {
                return response()->json([
                    "status" => "error",
                    "state" => false,
                    "message" => "ไม่มี DB นี้ในระบบ",
                ],400);
            }

            $dt = new \DateTime($Date);
            $year = (int)$dt->format('Y');
            $month = (int)$dt->format('m');
            $yearMonth = $dt->format('Y-m');

          if(in_array(strtolower($Shift),["day","night"])){
               $result2 = $this->db1->selectCollection("vwPowerConsumption".$DB."Hourly")->aggregate([
                    [
                         '$match' => [
                              "Shift" => $Shift,
                              "Year" => $year,
                              "Month" => $month,

                         ]
                    ],
                    [
                         '$group' => [
                              "_id" => [
                                   "DB" => '$DB',
                                   "Date" => '$Date',
                                   "Target" => '$Target',
                                   "Shift"=> '$Shift',
                              ],
                              "Expenses" => ['$sum' => '$Expenses'],
                              "kWh" => ['$sum' => '$kWh'],
                         ]
                    ],
                    [
                         '$project' => [
                              "_id" => 0,
                              "DB" => '$_id.DB',
                              "Date" => '$_id.Date',
                              "Target" => '$_id.Target',
                              "Expenses" => 1,
                              "kWh" => 1,
                              "Shift" => '$_id.Shift'
                         ]
                    ],
                    [
                         '$project' => [
                              "_id" => 0,
                              "DB" => 1,
                              "Date" => 1,
                              "Target" => [
                                   '$multiply'=> ['$Target', 24]
                              ],
                              "Expenses" => 1,
                              "kWh" => 1,
                              "Shift" => 1,
                         ]
                         ],
                    [
                         '$sort' => ["Date" => -1]
                    ]
               ]);

               $data = array();
               foreach ($result2 as $doc) array_push($data, $doc);

               return response()->json([
                "status"=> "success",
                "state" => true,
                "message"=> "Use electricity of Monthly",
                "data"=> $data
            ]);

          }else{

               $result2 = $this->db1->selectCollection("vwPowerConsumption".$DB."Hourly")->aggregate([
               [
                    '$match' => [
                         "Year" => $year,
                         "Month" => $month,

                    ]
               ],
               [
                    '$group' => [
                         "_id" => [
                              "DB" => '$DB',
                              "Date" => '$Date',
                              "Target" => '$Target',

                         ],
                         "Expenses" => ['$sum' => '$Expenses'],
                         "kWh" => ['$sum' => '$kWh'],
                    ]
               ],
               [
                    '$project' => [
                         "_id" => 0,
                         "DB" => '$_id.DB',
                         "Date" => '$_id.Date',
                         "Target" => '$_id.Target',
                         "Expenses" => 1,
                         "kWh" => 1,

                    ]
               ],
               [
                    '$project' => [
                         "_id" => 0,
                         "DB" => 1,
                         "Date" => 1,
                         "Target" => [
                              '$multiply'=> ['$Target', 24]
                         ],
                         "Expenses" => 1,
                         "kWh" => 1,
                         "Shift" => $Shift,
                    ]
                    ],
               [
                    '$sort' => ["Date" => -1]
               ]
          ]);
          $data = array();
          foreach ($result2 as $doc) array_push($data, $doc);

          return response()->json([
                    "status"=> "success",
                    "state" => true,
                    "message"=> "Use electricity of Monthly",
                    "data"=> $data,
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
     public function ecExpensesMonthly(Request $request)
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
                $request -> all(),
                [
                    'DB' => 'required|string',
                    'date' => 'required |string',
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
            $DB = $request->DB;
            $Date = $request->date;

            $allDB = ['MDB'];
            if (!in_array($DB, $allDB, true)) {
                return response()->json([
                    "status" => "error",
                    "state" => false,
                    "message" => "ไม่มี DB นี้ในระบบ",
                ],400);
            }

               $YearMonth = substr($Date,0,-3);

                    $result2 = $this->db1->selectCollection("vwPowerConsumption".$DB."Hourly")->aggregate([

                         [
                              '$match' => [
                                   "YearMonth" => $YearMonth,
                              ]
                         ],
                         [
                              '$group' => [
                                   "_id" => [
                                        "DB" => '$DB',
                                        "YearMonth" => '$YearMonth',
                                        "timeType" => '$TimeType'
                                   ],
                                   "Expenses" => ['$sum' => '$Expenses'],
                                   "kWh" => ['$sum' => '$kWh'],
                              ]
                         ],
                         [
                              '$project' => [
                                   "_id" => 0,
                                   "DB" => '$_id.DB',
                                   "YearMonth" => '$_id.YearMonth',
                                   "timeType" => '$_id.timeType',
                                   "Expenses" => 1,
                                   "kWh" => 1,
                              ]
                         ],
                         [
                              '$project' => [
                                   "_id" => 0,
                              ]
                         ],

                         [
                              '$sort' => ["YearMonth" => -1]
                         ]
                    ]);
                    $data = array();
                    foreach ($result2 as $doc) array_push($data, $doc);

                    return response()->json([
                        "status"=> "success",
                        "state" => true,
                        "message"=> "Expensive electricity of Monthly",
                        "data"=> $data,
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
     public function ecYearly(Request $request)
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
                $request -> all(),
                [
                    'DB' => 'required|string',
                    'year' => 'required |int',
                    'shift' => 'required |string',
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

            $DB = $request->DB;
            $Year = $request->year;
            $Shift = $request->shift;

            $allDB = ['MDB'];
            if (!in_array($DB, $allDB, true)) {
                return response()->json([
                    "status" => "error",
                    "state" => false,
                    "message" => "ไม่มี DB นี้ในระบบ",
                ],400);
            }
               $dt = new \DateTime($Year);
               $year = (int)$dt->format('Y');

          if(in_array(strtolower($Shift),["day","night"])){
               $result2 = $this->db1->selectCollection("vwPowerConsumption".$DB."Hourly")->aggregate([
                    [
                         '$match' => [
                              "Year" => $year,
                              "DB" => $DB,
                              "Shift" => strtolower($Shift) ,
                         ]
                    ],
                    [
                         '$group' => [
                              "_id" => [
                                   "DB" => '$DB',
                                   "YearMonth" => '$YearMonth',

                                   "Year"=> '$Year',
                                   "Month"=> '$Month',
                                   "Shift" => '$Shift'
                              ],
                              "Expenses" => ['$sum' => '$Expenses'],
                              "kWh" => ['$sum' => '$kWh'],
                              "Target" =>  ['$sum' => '$Target'],
                         ]
                    ],
                    [
                         '$project' => [
                              "_id" => 0,
                              "DB" => '$_id.DB',
                              "YearMonth" => '$_id.YearMonth',
                              "Target" => 1,
                              "Year" => '$_id.Year',
                              "Month" => '$_id.Month',
                              "Expenses" => 1,
                              "kWh" => 1,
                              "Shift" => '$_id.Shift',
                         ]
                    ],
                    [
                         '$sort' => ["Month" => 1]
                    ]
               ]);

               $data = array();
               foreach ($result2 as $doc) array_push($data, $doc);

               return response()->json([
                "status"=> "success",
                "state" => true,
                "message"=> "Use electricity of Yearly",
                "data"=> $data,
            ]);
          }
          else{
               // vwPowerConsumptionYearly
               $result2 = $this->db1->selectCollection("vwPowerConsumption".$DB."Hourly")->aggregate([
                    [
                         '$match' => [
                              "Year" => $year,
                              "DB" => $DB,
                         ]
                    ],
                    [
                         '$group' => [
                              "_id" => [
                                   "DB" => '$DB',
                                   "YearMonth" => '$YearMonth',
                                   // "Target" =>  '$Target',
                                   "Year"=> '$Year',
                                   "Month"=> '$Month',
                              ],
                              "Expenses" => ['$sum' => '$Expenses'],
                              "kWh" => ['$sum' => '$kWh'],
                              "Target" =>  ['$sum' => '$Target'],
                         ]
                    ],
                    [
                         '$project' => [
                              "_id" => 0,
                              "DB" => '$_id.DB',
                              "YearMonth" => '$_id.YearMonth',
                              "Target" => 1,
                              "Year" => '$_id.Year',
                              "Month" => '$_id.Month',
                              "Expenses" => 1,
                              "kWh" => 1,
                              "Shift" => $Shift,
                         ]
                    ],
                    [
                         '$sort' => ["Month" => 1]
                    ]
               ]);
               $data = array();
               foreach ($result2 as $doc) array_push($data, $doc);

               return response()->json([
                "status"=> "success",
                "state" => true,
                "message"=> "Use electricity of Yearly",
                "data"=> $data,
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

     public function ecExpensesYearly(Request $request)
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
                $request -> all(),
                [
                    'DB' => 'required|string',
                    'year' => 'required |int',
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

            $DB = $request->DB;
            $Year = $request->year;

            $allDB = ['MDB'];
            if (!in_array($DB, $allDB, true)) {
                return response()->json([
                    "status" => "error",
                    "state" => false,
                    "message" => "ไม่มี DB นี้ในระบบ",
                ],400);
            }


                    $result2 = $this->db1->selectCollection("vwPowerConsumption".$DB."Hourly")->aggregate([

                         [
                              '$match' => [
                                   "Year" => $Year,
                              ]
                         ],
                         [
                              '$group' => [
                                   "_id" => [
                                        "DB" => '$DB',
                                        "Year" => '$Year',
                                        "timeType" => '$TimeType'
                                   ],
                                   "Expenses" => ['$sum' => '$Expenses'],
                                   "kWh" => ['$sum' => '$kWh'],
                              ]
                         ],
                         [
                              '$project' => [
                                   "_id" => 0,
                                   "DB" => '$_id.DB',
                                   "Year" => '$_id.Year',
                                   "timeType" => '$_id.timeType',
                                   "Expenses" => 1,
                                   "kWh" => 1,
                              ]
                         ],
                         [
                              '$project' => [
                                   "_id" => 0,
                              ]
                         ],

                         [
                              '$sort' => ["Year" => -1]
                         ]
                    ]);
                    $data = array();
                    foreach ($result2 as $doc) array_push($data, $doc);

                    return response()->json([
                        "status"=> "success",
                        "state" => true,
                        "message"=> "Expensive electricity of Yearly",
                        "data"=> $data
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
     public function ecLastXDay(Request $request)
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
                $request -> all(),
                [
                    'DB' => 'required|string',
                    'date' => 'required |string',
                    'daycount' => 'required |int',
                    'shift' => 'required |string',
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

            $DB = $request->DB;
            $allDB = ['MDB'];
            if (!in_array($DB, $allDB, true)) {
                return response()->json([
                    "status" => "error",
                    "state" => false,
                    "message" => "ไม่มี DB นี้ในระบบ",
                ],400);
            }

            $Date = $request->date;
            $DayCount = $request->daycount;
            $Shift = $request->shift;
               $dt = new \DateTime($Date);
            //    $year = (int)$dt->format('Y');
            //    $month = (int)$dt->format('m');
               $date = $dt->format('Y-m-d');

               $dateObj = new \DateTime($Date);

               $dateArray = array();
               array_push($dateArray,$date);

               for($i = 1; $i < $DayCount; $i++) {
                    array_push($dateArray, $dt->modify('-1 day')->format('Y-m-d'));
               }

               if(in_array(strtolower($Shift),["day","night"])){
                    $result2 = $this->db1->selectCollection("vwPowerConsumption".$DB."Hourly")->aggregate([
                         [
                              '$match' => [
                                   "Date" => ['$in'=>
                                        $dateArray
                                        ],
                                   "Shift" => $Shift,
                              ]
                         ],
                         [
                              '$group' => [
                                   "_id" => [
                                        "DB" => '$DB',
                                        "Date"=> '$Date',
                                        "Shift" => '$Shift',
                                   ],
                                   "Expenses" => ['$sum' => '$Expenses'],
                                   "kWh" => ['$sum' => '$kWh'],
                                   "Target" => ['$max' => '$Target'],

                              ]
                         ],
                         [
                              '$project' => [
                                   "_id" => 0,
                                   "DB" => '$_id.DB',
                                   "Date" => '$_id.Date',
                                   "Target" => ['$multiply' => ['$Target',24]],
                                   "Expenses" => 1,
                                   "kWh" => 1,
                                   "Shift" => '$_id.Shift',
                              ]
                         ],
                         [
                              '$sort' => ["Date" => -1]
                         ],

                    ]);
                    $data = array();
                    foreach ($result2 as $doc) array_push($data, $doc);

                    return response()->json([
                        "status"=> "success",
                        "state" => true,
                        "message"=> "Get to monitor history electricity in $DayCount day ago",
                        "data"=> $data,
                    ]);

               }else{
                    $result2 = $this->db1->selectCollection("vwPowerConsumption".$DB."Hourly")->aggregate([
                         [
                              '$match' => [
                                   "Date" => ['$in'=>
                                        $dateArray
                                        ],
                              ]
                         ],
                         [
                              '$group' => [
                                   "_id" => [
                                        "DB" => '$DB',
                                        "Date"=> '$Date',
                                        // "Month"=> '$Month',
                                   ],
                                   "Expenses" => ['$sum' => '$Expenses'],
                                   "kWh" => ['$sum' => '$kWh'],
                                   "Target" => ['$max' => '$Target'],

                              ]
                         ],
                         [
                              '$project' => [
                                   "_id" => 0,
                                   "DB" => '$_id.DB',
                                   "Date" => '$_id.Date',
                                   "Target" => ['$multiply' => ['$Target',24]],
                                   "Expenses" => 1,
                                   "kWh" => 1,
                                   "Shift" => $Shift,
                              ]
                         ],
                         [
                              '$sort' => ["Date" => -1]
                         ],
                    ]);
                    $data = array();
                    foreach ($result2 as $doc) array_push($data, $doc);

                    return response()->json([
                        "status"=> "success",
                        "state" => true,
                        "message"=> "Get to monitor history electricity in $DayCount day ago",
                        "data"=> $data,
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

     public function ecExpensesLastXDay(Request $request)
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
                $request -> all(),
                [
                    'DB' => 'required|string',
                    'date' => 'required |string',
                    'daycount' => 'required |int'
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

            $DB = $request->DB;
            $date = $request->date;
            $dayCount =$request -> daycount;
            $dt = new \DateTime($date);
            $date = $dt->format('Y-m-d');

            $endDate = $dt->modify('-'.$dayCount.'day')->format('Y-m-d');

            $dt = new \DateTime($date);
            $dateArray = array();
            array_push($dateArray,$date);


            for($i = 1; $i < $dayCount; $i++) {
                array_push($dateArray, $dt->modify('-1 day')->format('Y-m-d'));
           }

            $allDB = ['MDB'];
            if (!in_array($DB, $allDB, true)) {
                return response()->json([
                    "status" => "error",
                    "state" => false,
                    "message" => "ไม่มี DB นี้ในระบบ",
                ],400);
            }


                    $result2 = $this->db1->selectCollection("vwPowerConsumption".$DB."Hourly")->aggregate([

                         [
                              '$match' => [
                                //    "date" => $date,
                                   "Date" => ['$in'=>
                                        $dateArray
                                        ],
                              ]
                         ],
                         [
                              '$group' => [
                                   "_id" => [
                                        "DB" => '$DB',
                                        "timeType" => '$TimeType',
                                        // "Date" => '$Date'
                                   ],
                                   "Expenses" => ['$sum' => '$Expenses'],
                                   "kWh" => ['$sum' => '$kWh'],
                              ]
                         ],
                         [
                              '$project' => [
                                   "_id" => 0,
                                   "DB" => '$_id.DB',
                                //    "Date" => '$_id.Date',
                                   "timeType" => '$_id.timeType',
                                   "Expenses" => 1,
                                   "kWh" => 1,
                                   "StartDate" => $date,
                                    "EndDate" => $endDate
                              ]
                         ],


                         [
                              '$sort' => ["Date" => -1]
                         ]
                    ]);
                    $data = array();
                    foreach ($result2 as $doc) array_push($data, $doc);

                    return response()->json([
                        "status"=> "success",
                        "state" => true,
                        "message"=> "Expensive electricity of $dayCount days",
                        "data"=> $data
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
     public function ecVoltagePastXMinutes(Request $request)
     {
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
                    'Minutes' => 'required|int',
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

            $Minutes = $request->Minutes;
            $dt = new \DateTime();
            $timezone = new \DateTimeZone('Asia/Bangkok');
            $dt->setTimezone($timezone);
            $date = $dt->format('Y-m-d H:i:00');

            $dateArray = array();
            array_push($dateArray,$date);

            for($i = 1; $i < $Minutes; $i++) {
                array_push($dateArray, $dt->modify('-1 minutes')->format('Y-m-d H:i:00'));
            }

             $result2 = $this->db1->selectCollection("vWPowerConsumptionPastXMinutes")->aggregate([
                  [
                       '$match' => [
                            "Datetime" => ['$in'=>
                            $dateArray
                            ],
                       ]
                  ],
                  [
                       '$project' => [
                            "_id" => 0,
                            "DB" => 1,
                            "Voltage" => 1,
                            "Datetime" => 1,
                       ]
                  ],
                  [
                       '$sort' => ["Datetime" => -1]
                  ],

             ]);

            $data = array();
            foreach ($result2 as $doc)
            array_push($data, $doc);

            return response()->json([
                "status"=> "success",
                "state" => true,
                "message"=> "Use Voltage of electricity in $Minutes minutes",
                "data"=> $data,
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
   public function ecPowerConsumptionPastXMinutes(Request $request){

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
                    'Minutes' => 'required|int',
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

         $dt = new \DateTime();
         $timezone = new \DateTimeZone('Asia/Bangkok');
         $dt->setTimezone($timezone);
         $date = $dt->format('Y-m-d H:i:00');
         $Minutes = $request->Minutes;


         $dateArray = array();
         array_push($dateArray,$date);

         for($i = 1; $i < $Minutes; $i++) {
              array_push($dateArray, $dt->modify('-1 minutes')->format('Y-m-d H:i:00'));
         }

         $result2 = $this->db1->selectCollection("vWPowerConsumptionPastXMinutes")->aggregate([
              [
                   '$match' => [
                        "Datetime" => ['$in'=>
                        $dateArray
                        ],
                   ]
              ],
              [
                   '$project' => [
                        "_id" => 0,
                        "DB" => 1,
                        "kW" => 1,
                        "Datetime" => 1,
                   ]
              ],
              [
                   '$sort' => ["Datetime" => -1]
              ],

         ]);
         $data = array();
         foreach ($result2 as $doc) array_push($data, $doc);

         return response()->json([
            "status"=> "success",
            "state" => true,
            "message"=> "Use Power consumption in $Minutes minutes",
            "data"=> $data ,
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
public function ecCurrentPastXMinutes(Request $request){

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
                    'Minutes' => 'required|int',
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

         $dt = new \DateTime();
         $timezone = new \DateTimeZone('Asia/Bangkok');
         $dt->setTimezone($timezone);
         $date = $dt->format('Y-m-d H:i:00');
         $Minutes = $request->Minutes;

         $dateArray = array();
         array_push($dateArray,$date);

         for($i = 1; $i < $Minutes; $i++) {
              array_push($dateArray, $dt->modify('-1 minutes')->format('Y-m-d H:i:00'));
         }

         $result2 = $this->db1->selectCollection("vWPowerConsumptionPastXMinutes")->aggregate([
              [
                   '$match' => [
                        "Datetime" => ['$in'=>
                        $dateArray
                        ],
                        "DB" => 'MDB',
                   ]
              ],
              [
                   '$project' => [
                        "_id" => 0,
                        "DB" => 1,
                        "Current" => 1,
                        "Datetime" => 1,
                   ]
              ],
              [
                   '$sort' => ["Datetime" => -1]
              ],

         ]);
         $data = array();
         foreach ($result2 as $doc) array_push($data, $doc);

         return response()->json([
            "status"=> "success",
            "state" => true,
            "message"=> "Use Current of electricity in $Minutes minutes",
            "data"=> $data
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

public function ecElectricPastXMinutes(Request $request)
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
            $request -> all(),
            [
                'Minutes' => 'required|int',
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

        $dt = new \DateTime();
        $timezone = new \DateTimeZone('Asia/Bangkok');
        $dt->setTimezone($timezone);
        $date = $dt->format('Y-m-d H:i:00');

        $Minutes =$request->Minutes;

          $dateArray = array();
          array_push($dateArray,$date);

          for($i = 1; $i < $Minutes; $i++) {
               array_push($dateArray, $dt->modify('-1 minutes')->format('Y-m-d H:i:00'));
          }

          $result2 = $this->db1->selectCollection("vWPowerConsumptionPastXMinutes")->aggregate([
               [
                    '$match' => [
                         "Datetime" => ['$in'=>
                         $dateArray
                         ],
                         "DB" => 'MDB',
                    ]
               ],
               [
                    '$setWindowFields' => [
                         "partitionBy" => null,
                         "sortBy" => [
                              "Datetime" => 1,
                         ],
                         "output" => [
                              "kWhLag" => [
                                   '$shift' => [
                                        "output" => '$kWh',
                                        "by" => -1,
                                        "default" => null,
                                        ]
                              ]
                         ],
                    ]
               ],
               [
                    '$project' => [
                         "DB" => 1,
                         "kWh" => [
                              '$subtract'=> ['$kWh', '$kWhLag']
                         ],
                         "Datetime" => 1,
                    ]
               ],
               [
                    '$sort' => [
                         "Datetime" => -1 ]
               ]

          ]);
          $data = array();
          foreach ($result2 as $doc) array_push($data, $doc);

          return response()->json([
            "status"=> "success",
            "state" => true,
            "message"=> "Use Electricity in $Minutes minutes",
            "data"=> $data
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

   public function ecLastDBAll(Request $request)
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

             $dt = new \DateTime();
             $timezone = new \DateTimeZone('Asia/Bangkok');
             $dt->setTimezone($timezone);
             $date = $dt->modify('-1 minutes')->format('Y-m-d H:i:00');
             $dateArray = array();
             array_push($dateArray,$date);
             $result2 = $this->db1->selectCollection("vWPowerConsumptionPastXMinutes")->aggregate([
                  [
                       '$match' => [
                            "Datetime" => ['$in'=>
                            $dateArray
                            ],
                       ]
                  ],
                  [
                       '$project' => [
                            "_id" => 0,
                            "DB" => 1,
                            "Voltage"=> 1,
                            "kWh" => 1,
                            "Datetime" => 1,
                            "Current" => 1,
                            "Power"=> '$kW',
                            "Unit"=> '$kWh'
                       ]
                  ],
                  [
                       '$sort' => ["Datetime" => -1]
                  ],

             ]);
             $data = array();
             foreach ($result2 as $doc) array_push($data, $doc);

             return response()->json([
                "status"=> "success",
                "state" => true,
                "message"=> "Get all to monitor last value of electricity each MDB ",
                "data"=> $data
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
   public function ecAllTarget(Request $request)
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

             $result2 = $this->db1->selectCollection("vwAllDBTarget")->aggregate([

                  [
                       '$sort' => ["Date" => -1]
                  ],

             ]);
            $data = array();
            foreach ($result2 as $doc) array_push($data, $doc);

             return response()->json([
                "status"=> "success",
                "state" => true,
                "message"=> "Get all to check each Target",
                "data"=> $data,
            ]);

            } catch (\Exception $e) {
                return response()->json([
                    "status" => "error",
                    "message" => $e->getMessage(),
                    "data" => [],
                ]);
            }
   }
   public function ecVariableChk(Request $request)
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

               $result2 = $this->db1->selectCollection("vwVariables")->aggregate([

                    [
                         '$project' => [
                              "_id" => 0,
                              "Type" => 1,
                              "FT" => 1,
                              "PF" => 1,
                              "Peak" => 1,
                              "OffPeak" => 1,
                              "Exceed" => 1,
                              "Service" => 1,
                              "GasPriceLPG" =>1,
                              "GasPriceNitrogen" =>1,
                              "GasPriceOxygen" =>1,
                              "TimeStart"  => [

                                   '$dateToString' => [
                                     'date' => '$TimeStart',
                                     'format' => "%Y-%m-%d",
                                   ],

                                ],
                                "TimeEnd"  => [

                                    '$dateToString' => [
                                      'date' => '$TimeEnd',
                                      'format' => "%Y-%m-%d",
                                    ],

                          ]
                         ]
                    ],

               ]);
               $data = array();
               foreach ($result2 as $doc) array_push($data, $doc);

               return response()->json([
                "status"=> "success",
                "state" => true,
                "message"=> "Get to monitor calculator variable of Electricity",
                "data"=> $data,
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
     public function notificationHistory(Request $request)
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

               $result2 = $this->db1->selectCollection("vwNotificationHistory")->aggregate([
                    [
                         '$sort' => ["_id" => -1]
                    ],
                    [
                         '$project' => [
                              "_id" => 0,
                              "NotifyID" => [
                                   '$toString' => '$_id'
                              ],
                              "Topic" => 1,
                              "Average" => 1,
                              "Current" => 1,
                              "Note" => 1,
                              "IncidentDate" => 1,
                              "RevisionDate" => 1,
                              "Advice" => 1,
                              "Excess" => 1,
                              "IsConfirm" => 1,
                              "Datetime"  => [

                                   '$dateToString' => [
                                     'date' => '$Datetime',
                                     'format' => "%Y-%m-%d %H:%M:%S",
                                   ],
                         ]
                         ]
                    ],

               ]);
               $data = array();
               foreach ($result2 as $doc) array_push($data, $doc);

               return response()->json([
                "status"=> "success",
                "state" => true,
                "message"=> "Get to monitor notification history",
                "data"=> $data,
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
     public function weather(Request $request)
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
                "status"=> "success",
                "state" => true,
                "message"=> "Get to monitor weather",
                "data"=> $data,
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
       public function iRPA(Request $request)
     {
          try {
                $header = $request->header('Authorization');
                $jwt = $this->jwtUtils->verifyToken($header);
                if (!$jwt->state) return response()->json([
                    "status" => "error",
                    "state"=> false,
                    "message" => "Unauthorized",
                    "data" => [],
                ], 401);


               $sql  = "SELECT
               comName,premise,kWh,costAmountAfterVat
               ,CAST(LEFT(p_period, 2) AS INT) AS [Month]
               ,CAST(RIGHT(p_period, 2) AS INT) + IIF(YEAR(GETDATE()) + 543 >= 2600, 2600, 2500) - 543 AS [Year]
               FROM [iRPA].[dbo].[V_ElectricityBills] WHERE premise= '88/99';";

            //    $result = $this->flexModel->query($sql)->getResult();

               return response()->json([
                "status"=> "success",
                "state"=> true,
                "message"=> "Get to moniter bill of electical payroll",
                // "data"=> $result,
            ]);

            } catch (\Exception $e) {
                return response()->json([
                    "status" => "error",
                    "state"=> false,
                    "message" => $e->getMessage(),
                    "data" => [],
                ]);
            }
     }



}


