<?php

use App\Http\Controllers\Api\V1\ProspectingJobController;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/prospecting-jobs', [ProspectingJobController::class, 'store']);
});
