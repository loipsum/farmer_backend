<?php

use App\Http\Controllers\AgentChangePasswordController;
use App\Http\Controllers\AgentItemListController;
use App\Http\Controllers\AgentLoginController;
use App\Http\Controllers\AgentLogoutController;
use App\Http\Controllers\AgentRecentEntryController;
use App\Http\Controllers\AgentUpdateController;
use App\Http\Controllers\DistrictController;
use App\Http\Controllers\EntryController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\LogoutController;
use App\Http\Controllers\MarketController;
use App\Http\Controllers\MarketLocationController;
use App\Http\Controllers\MarketPhotoController;
use App\Http\Controllers\MonthlyReportController;
use App\Http\Controllers\PublicItemController;
use App\Http\Controllers\UserController;
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

Route::get('/sanctum/csrf-cookie', function () {
    return response('', 204);
});

//? login
Route::post('login', LoginController::class);
Route::post('agent/login', AgentLoginController::class)->name('agent.login');

//? authentication required
Route::middleware('auth:sanctum')->group(function () {
    Route::post('logout', LogoutController::class);
    Route::post('agent/logout', AgentLogoutController::class);

    //? get auth user details
    Route::get('user', [UserController::class, 'show'])->name('user.show');

    Route::match(['put', 'patch'], 'user/update', [UserController::class, 'update'])->name('user.update');

    //? admin guard
    Route::middleware('admin')->group(function () {

        Route::apiResource('item', ItemController::class)->except(['index', 'show']);
        Route::apiResource('market', MarketController::class)->except(['index', 'show']);
        Route::apiResource('district', DistrictController::class)->except(['index', 'show']);
        Route::apiResource('entry', EntryController::class)->except(['index', 'show', 'store']);

        Route::get('users', [UserController::class, 'index'])->name('user.index');
        Route::delete('user/{user}', [UserController::class, 'destroy'])->name('user.destroy');
        Route::post('user', [UserController::class, 'store'])->name('user.store');

        //? update admin details
        Route::match(['put', 'patch'], 'admin/{user}', [UserController::class, 'update'])->name('admin.update');

        //? update 1.agent details 2.password change
        Route::match(['put', 'patch'], 'agent/{user}', AgentUpdateController::class)->name('agent.update');
        Route::match(['put', 'patch'], 'agent/password/{user}', AgentChangePasswordController::class)->name('agent.password');

        //? restore resources
        // Route::post('district/restore/all', [DistrictController::class, 'restoreAll'])->name('district.restoreAll');
        // Route::post('entry/restore/all', [EntryController::class, 'restoreAll'])->name('entry.restoreAll');
        // Route::post('item/restore/all', [ItemController::class, 'restoreAll'])->name('item.restoreAll');
        // Route::post('market/restore/all', [MarketController::class, 'restoreAll'])->name('market.restoreAll');


    });

    //? agent guard`
    Route::middleware('agent')
        ->prefix('agent')
        ->as('agent.')
        ->group(
            function () {
                Route::get('recent-entries', AgentRecentEntryController::class)->name('recent-entries');
                Route::get('item-list', AgentItemListController::class)->name('item-list');
                Route::apiResource('entry', EntryController::class)->only('store');
            }
        );
});

Route::apiResource('item', ItemController::class)->only(['index', 'show']);

Route::apiResource('market', MarketController::class)->only(['index', 'show']);

Route::apiResource('district', DistrictController::class)->only(['index', 'show']);

Route::apiResource('entry', EntryController::class)->only(['index', 'show']);

Route::get('market-loc', MarketLocationController::class)->name('market-location');

Route::group(
    [
        'prefix' => 'public',
        'as' => 'public.'
    ],
    function () {
        Route::apiResource('item', PublicItemController::class)->only('index', 'show');
    }
);

Route::prefix('photo')
    ->as('photo.')
    ->group(function () {
        Route::get('{market}/{item}', MarketPhotoController::class)->name('item.show');
    });

Route::group(
    [
        'prefix' => 'report',
        'as' => 'report.'
    ],
    function () {
        Route::apiResource('monthly', MonthlyReportController::class)->only('index', 'show')->parameter('monthly', 'item');
    }
);
