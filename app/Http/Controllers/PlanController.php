<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Http\Libraries\JWT\JWTUtils;
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;

class PlanController extends Controller
{
    private $flexModel;
    private $jwtUtils;
    private $mongo;
    private $db1;
    private function MongoDBObjectId($id)
    {
        try {
            return new ObjectId($id);
        } catch (\Exception $e) {
            // Log or print the exception message for debugging
            dd($e->getMessage());
            return null;
        }
    }

    private function MongoDBUTCDatetime(int $time)
    {
        try {
            return new UTCDateTime($time);
        } catch (\Exception $e) {
            return null;
        }
    }
    public function __construct()
    {
        $this->mongo = new \MongoDB\Client("mongodb://iiot-center2:%24nc.ii0t%402o2E@10.0.0.7:27017/?authSource=admin");
        $this->db1 = $this->mongo->selectDatabase("IPSS_B7");
        $this->jwtUtils = new JWTUtils();
    }

    public function setECTarget(Request $request)
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
                    'data' => 'required|array',
                ]
            );


            $role = $request->Role;
            if ($role != 'admin'){
                            return response()->json([
                                "status" => "error",
                                "message" => "Cannot access, you are not admin",
                                "data" => []
                            ]);
                        }

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

            $data = $request->data;

            $dataTargetMDB = array();

            foreach ($data as $info) {

                $onPeakMDB = (float)$info['MDB'] * 0.6;
                $offPeakMDB = (float)$info['MDB']  - $onPeakMDB;
                $Date = (string)$info['date'];
                $InsertTargetMDB = ["OnPeakTarget" => $onPeakMDB, "OffPeakTarget" => $offPeakMDB, "Date" => $Date];
                array_push($dataTargetMDB, $InsertTargetMDB);
            }

            $this->db1->selectCollection("TargetMDB")->insertMany($dataTargetMDB);

            return response()->json([
                "status" => "seccess",
                "state" => true,
                "message" => "Set Electrical target Successfully",
                "data" => $dataTargetMDB,
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

    public function setWTTarget(Request $request)
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
                    'data' => 'required|array',
                ]
            );

            $role = $request->Role;
            if ($role != 'admin'){
                            return response()->json([
                                "status" => "error",
                                "message" => "Cannot access, you are not admin",
                                "data" => []
                            ]);
                        }

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

            $data = $request->data;
            foreach ($data as $info) {
                // $info = (object)$info;
                $Target = (float)$info['Target'];
                $Date = (string)$info['date'];
                $InsertWaterTarget = ["Date" => $Date, "Target" => $Target];

                $this->db1->selectCollection("WaterTarget")->insertOne($InsertWaterTarget);
            }

            return response()->json([
                "status" => "seccess",
                "state" => true,
                "message" => "Set Water Target Successfully",
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

    public function updateNotify(Request $request)
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
                    'NotifyID' => 'required|string',
                    'IncidentDate' => 'required|string',
                    'RevisionDate' => 'required|string',
                    'Advice' => 'required|string',
                    'Note' => 'required|string',
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

            $NotifyID = $request->NotifyID;
            $IncidentDate = $request->IncidentDate;
            $RevisionDate = $request->RevisionDate;
            $Advice = $request->Advice;
            $Note = $request->Note;

            $filter = ["_id" => $this->MongoDBObjectId($NotifyID)];
            $update = ['$set' => [
                'Note' => $Note,
                'IncidentDate' => $IncidentDate,
                'RevisionDate' => $RevisionDate,
                'Advice' => $Advice,
            ]];

            $result = $this->db1->NotificationHistory->updateOne($filter, $update);

            return response()->json([
                "status" => "seccess",
                "state" => true,
                "message" => "Update NotificationHistory Successfully",
                "data" => $result,

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

    public function ecVariable(Request $request)
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
                    'Type' => 'required|string',
                    // 'FT' => 'required|string',
                    // 'PF' => 'required|string',
                    // 'Peak' => 'required|string',
                    // 'OffPeak' => 'required|string',
                    // 'Exceed' => 'required|string',
                    // 'Service' => 'required|string',
                    'Date' => 'required|string',
                    // 'GasPriceLPG' => 'required|string',
                    // "GasNitrogenSize"=>'required|string',
                    // "GasPriceNitrogen"=>'required|string',
                    // "GasOxygenSize"=>'required|string',
                    // "GasPriceOxygen"=>'required|string',
                ]
            );
            $role = $request->Role;
            if ($role != 'admin'){
                            return response()->json([
                                "status" => "error",
                                "message" => "Cannot access, you are not admin",
                                "data" => []
                            ]);
                        }

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

            $type = $request->Type;
            $FT = $request->FT;
            $PF = $request->PF;
            $peak = $request->Peak;
            $offPeak = $request->OffPeak;
            $exceed = $request->Exceed;
            $service = $request->Service;

            $gasPriceLPG = $request->GasPriceLPG;
            $gasNitrogenSize = $request->GasNitrogenSize;
            $gasPriceNitrogen = $request->GasPriceNitrogen;
            $gasOxygenSize = $request->GasOxygenSize;
            $gasPriceOxygen = $request->GasPriceOxygen;
            $gasPriceNitrogenNet = $gasPriceNitrogen / $gasNitrogenSize;
            $gasPriceOxygenNet = $gasPriceOxygen / $gasOxygenSize;

            $date = $request->Date;

            $filter = ["Type" => (int)$type];
            //    $update = ['$set' => [
            //     'FT' => (float)$FT,
            //     'PF' => (float)$PF,
            //     'Peak' => (float)$peak,
            //     'OffPeak' => (float)$offPeak,
            //     'Exceed' => (float)$exceed,
            //     'Service' => (float)$service,
            //     'GasPriceLPG' => (float)$gasPriceLPG,
            //     'GasPriceNitrogen' => (float)$gasPriceNitrogenNet,
            //     'GasPriceOxygen' => (float)$gasPriceOxygenNet,
            //     'Date' => $date,
            //    ]];

            $insertVariebles = [
                'FT' => (float)$FT,
                'PF' => (float)$PF,
                'Peak' => (float)$peak,
                'OffPeak' => (float)$offPeak,
                'Exceed' => (float)$exceed,
                'Service' => (float)$service,
                'GasPriceLPG' => (float)$gasPriceLPG,
                'GasPriceNitrogen' => (float)$gasPriceNitrogenNet,
                'GasPriceOxygen' => (float)$gasPriceOxygenNet,
                'Date' => $this->MongoDBUTCDatetime((new \DateTime($date))->getTimestamp() * 1000),
            ];

            $result = $this->db1->selectCollection("Variables")->insertOne($insertVariebles);

            return response()->json([
                "status" => "seccess",
                "state" => true,
                "message" => "Update Variables Successfully",
                "data" => $result,
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

    //    public function setGasNitrogenTarget(Request $request)
    //    {
    //       try {
    //            $header = $request->header('Authorization');
    //            $jwt = $this->jwtUtils->verifyToken($header);
    //            if (!$jwt->state) return response()->json([
    //                "status" => "error",
    //                "state" => false,
    //                "message" => "Unauthorized",
    //                "data" => [],
    //            ], 401);

    //            $validators = Validator::make(
    //                $request -> all(),
    //                [
    //                    'data' => 'required|array',
    //                ]
    //            );

    //            if ($validators->fails()) {
    //                return response()->json([
    //                    "status" => "error",
    //                    "state" => false,
    //                    "message" => "กรอกข้อมูลไม่ครบถ้วน",
    //                    "data" => [
    //                        [
    //                            "validators" => $validators->errors()
    //                        ]
    //                    ]
    //                ], 400);
    //            }

    //            $data = $request->data;
    //            $dataTargetGasNitrogen = array();

    //            foreach ($data as $info) {
    //             $Target = (float)$info['Target'];
    //             $Date = (String)$info['date'];
    //             $InsertGasN2Target = ["date" => $Date,"Target" => $Target ];

    //             array_push($dataTargetGasNitrogen, $InsertGasN2Target);
    //            }

    //           $this->db1->selectCollection("NitrogenTarget")->insertMany($dataTargetGasNitrogen);

    //           return response()->json([
    //            "status" => "seccess",
    //            "state" => true,
    //           "message" => "Set target of Nitrogen Gas Successfully",
    //           "data" => $dataTargetGasNitrogen,
    //         ]);

    //         } catch (\Exception $e) {
    //             return response()->json([
    //                 "status" => "error",
    //                 "state" => false,
    //                 "message" => $e->getMessage(),
    //                 "data" => [],
    //             ]);
    //         }
    //    }

    //    public function setGasOxygenTarget(Request $request)
    //    {
    //       try {
    //            $header = $request->header('Authorization');
    //            $jwt = $this->jwtUtils->verifyToken($header);
    //            if (!$jwt->state) return response()->json([
    //                "status" => "error",
    //                "state" => false,
    //                "message" => "Unauthorized",
    //                "data" => [],
    //            ], 401);

    //            $validators = Validator::make(
    //                $request -> all(),
    //                [
    //                    'data' => 'required|array',
    //                ]
    //            );

    //            if ($validators->fails()) {
    //                return response()->json([
    //                    "status" => "error",
    //                    "state" => false,
    //                    "message" => "กรอกข้อมูลไม่ครบถ้วน",
    //                    "data" => [
    //                        [
    //                            "validators" => $validators->errors()
    //                        ]
    //                    ]
    //                ], 400);
    //            }

    //            $data = $request->data;
    //            $dataTargetGasOxygen = array();

    //            foreach ($data as $info) {
    //             $Target = (float)$info['Target'];
    //             $Date = (String)$info['date'];
    //             $InsertGasO2Target = ["date" => $Date,"Target" => $Target ];

    //             array_push($dataTargetGasOxygen, $InsertGasO2Target);
    //            }

    //           $this->db1->selectCollection("OxygenTarget")->insertMany($dataTargetGasOxygen);

    //           return response()->json([
    //            "status" => "seccess",
    //            "state" => true,
    //           "message" => "Set target of Oxygen Gas Successfully",
    //           "data" => $dataTargetGasOxygen,
    //         ]);

    //         } catch (\Exception $e) {
    //             return response()->json([
    //                 "status" => "error",
    //                 "state" => false,
    //                 "message" => $e->getMessage(),
    //                 "data" => [],
    //             ]);
    //         }
    //    }
    //    public function setGasLPGTarget(Request $request)
    //    {
    //       try {
    //            $header = $request->header('Authorization');
    //            $jwt = $this->jwtUtils->verifyToken($header);
    //            if (!$jwt->state) return response()->json([
    //                "status" => "error",
    //                "state" => false,
    //                "message" => "Unauthorized",
    //                "data" => [],
    //            ], 401);

    //            $validators = Validator::make(
    //                $request -> all(),
    //                [
    //                    'data' => 'required|array',
    //                ]
    //            );

    //            if ($validators->fails()) {
    //                return response()->json([
    //                    "status" => "error",
    //                    "state" => false,
    //                    "message" => "กรอกข้อมูลไม่ครบถ้วน",
    //                    "data" => [
    //                        [
    //                            "validators" => $validators->errors()
    //                        ]
    //                    ]
    //                ], 400);
    //            }

    //            $data = $request->data;
    //            $dataTargetGasLPG = array();

    //            foreach ($data as $info) {
    //             $Target = (float)$info['Target'];
    //             $Date = (String)$info['date'];
    //             $InsertGasLPGTarget = ["date" => $Date,"Target" => $Target ];
    //             array_push($dataTargetGasLPG, $InsertGasLPGTarget);
    //            }

    //           $this->db1->selectCollection("LPGTarget")->insertMany($dataTargetGasLPG);

    //           return response()->json([
    //            "status" => "seccess",
    //            "state" => true,
    //           "message" => "Set target of LPG Gas Successfully",
    //           "data" => $dataTargetGasLPG,
    //         ]);

    //         } catch (\Exception $e) {
    //             return response()->json([
    //                 "status" => "error",
    //                 "state" => false,
    //                 "message" => $e->getMessage(),
    //                 "data" => [],
    //             ]);
    //         }
    //    }

    //Set gas target
    public function setGasTarget(Request $request)
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
                    'data' => 'required|array',
                ]
            );

            $role = $request->Role;
            if ($role != 'admin'){
                            return response()->json([
                                "status" => "error",
                                "message" => "Cannot access, you are not admin",
                                "data" => []
                            ]);
                        }

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

            $data = $request->data;
            $dataTargetLPG = array();
            $dataTargetNitrogen = array();
            $dataTargetOxygen = array();

            foreach ($data as $info) {

                $Date1 = (string)$info['Date'];
                $InsertTargetLPG = ["Target" => $info['LPG'], "Date" => $Date1];
                array_push($dataTargetLPG, $InsertTargetLPG);

                $Date2 = (string)$info['Date'];
                $InsertTargetNitrogen = ["Target" => $info['Nitrogen'], "Date" => $Date2];
                array_push($dataTargetNitrogen, $InsertTargetNitrogen);


                $Date3 = (string)$info['Date'];
                $InsertTargetOxygen = ["Target" => $info['Oxygen'], "Date" => $Date3];
                array_push($dataTargetOxygen, $InsertTargetOxygen);
            }

            $this->db1->selectCollection("LPGTarget")->insertMany($dataTargetLPG);
            $this->db1->selectCollection("NitrogenTarget")->insertMany($dataTargetNitrogen);
            $this->db1->selectCollection("OxygenTarget")->insertMany($dataTargetOxygen);

            return response()->json([
                "status" => "seccess",
                "state" => true,
                "message" => "Set Gas target Successfully",
                "data" => [
                    [
                        "TargetLPG" => $dataTargetLPG,
                        "TargetNitrogen" => $dataTargetNitrogen,
                        "TargetOxygen" => $dataTargetOxygen,

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
}
