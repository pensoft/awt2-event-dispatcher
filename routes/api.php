<?php

use App\Api\V1\Controllers\Notifications\NotificationsController;
use App\Api\V1\Controllers\Pdf\PdfController;
use App\Api\V1\Controllers\Tasks\TasksController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

use Dingo\Api\Routing\Router;
use Illuminate\Support\Facades\Auth;

/** @var Router $api */
$api = app(Router::class);

$api->version('v1', function (Router $api) {
    $api->group(['prefix' => 'notifications'], function (Router $api) {
        Auth::guard()->user();
        $api->post('/', NotificationsController::class);
    });

    $api->group(['prefix' => 'tasks'], function (Router $api) {
        Auth::guard()->user();
        $api->get('/', [TasksController::class, 'getTasks'])->name('get.tasks');
    });

    $api->group(['prefix' => 'pdf'], function (Router $api) {
        Auth::guard()->user();
        $api->post('/export', PdfController::class);
    });
});
