<?php


use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\WeatherController;
use App\Http\Controllers\ElectricityController;
use App\Http\Controllers\TestController;
use App\Http\Controllers\WaterController;
use App\Http\Controllers\PlanController;
use App\Http\Controllers\GasController;
use App\Http\Controllers\iRPAController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });

Route::prefix('test')->controller(TestController::class)->group(function () {
    Route::post('/', 'test');
});


Route::prefix('iPSS')->controller(AuthController::class)->group(function () {
    Route::post('/login', 'Login');
    Route::post('/change-password', 'changPassword');
});

Route::prefix('iPSS')->controller(ElectricityController::class)->group(function () {
    Route::post('/ec-daily', 'ecDaily');
    Route::post('/ec-monthly', 'ecMonthly');
    Route::post('/ec-yearly', 'ecYearly');
    Route::post('/ec-last-x-day', 'ecLastXDay');
    Route::post('/ec-xday-expenses', 'ecExpensesLastXDay');

    Route::post('/ec-expenses-daily', 'ecExpensesDaily');
    Route::post('/ec-expenses-yearly', 'ecExpensesYearly');
    Route::post('/ec-expenses-monthly', 'ecExpensesMonthly');

    Route::post('/ec-electric-past-x-minutes', 'ecElectricPastXMinutes');
    Route::post('/ec-voltage-past-x-minutes', 'ecVoltagePastXMinutes');
    Route::post('/ec-current-past-x-minutes', 'ecCurrentPastXMinutes');
    Route::post('/ec-power-consumption-past-x-minutes', 'ecPowerConsumptionPastXMinutes');

    Route::get('/ec-variable-check', 'ecVariableChk');
    Route::get('/ec-last-db-all', 'ecLastDBAll');
    Route::get('/ec-all-target', 'ecAllTarget');

    Route::get('/notification', 'notificationHistory');

});

// WeatherController
Route::prefix('iPSS')->controller(WeatherController::class)->group(function () {
    Route::get('/weather', 'weather');
});



Route::prefix('iPSS')->controller(WaterController::class)->group(function () {
    Route::post('/wt-daily', 'wtDaily');
    Route::post('/wt-monthly', 'wtMonthly');
    Route::post('/wt-yearly', 'wtYearly');
    Route::post('/wt-last-day', 'wtLastXDay');
    Route::get('/wt-all-target', 'wtAllTarget');
    Route::get('/wt-15points', 'wt15Points');
    Route::get('/wt-60points', 'wt60Points');
    Route::post('/wt-unit-pastx', 'wtUnitPastXPoints');
});



Route::prefix('iPSS')->controller(PlanController::class)->group(function () {
    Route::post('/set-ec-target', 'setECTarget');
    Route::post('/set-wt-target', 'setWTTarget');
    Route::post('/update-notify', 'updateNotify');
    Route::post('/update-ec-variable', 'ecVariable');
    Route::post('/set-gas-target', 'setGasTarget');
});

Route::prefix('iPSS')->controller(GasController::class)->group(function () {
    Route::post('/gas-daily', 'gasDaily');
    Route::post('/gas-monthly', 'gasMonthly');
    Route::post('/gas-yearly', 'gasYearly');
    Route::post('/gas-last-xday', 'gasLastXDay');
    Route::get('/gas-all-target', 'gasAllTarget');
    Route::post('/gas-flowrate', 'gasFlowrateUnitPastXPoints');
    Route::post('/gas-velocity', 'gasVelocityPastXPoints');
    Route::post('/gas-cumulative', 'gasCumulativePastXPoints');
});

Route::prefix('iPSS')->controller(iRPAController::class)->group(function () {
    Route::get('/iRPA', 'RPA');
});
