<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ElifController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\InvoiceController;


//PUBLIC ROUTES
//route to all invoices
Route::get('/invoices', [InvoiceController::class, 'index']);

//route to specific invoice
Route::get('/invoices/{id}', [InvoiceController::class, 'show']);

//route to all imports
Route::get('/imports', [ImportController::class, 'index']);

Route::get('/imports/{id}', [ImportController::class, 'show']);


//AUTH ROUTES
//route to login
// Route::post('/login', [AuthController::class, 'login']);




// //ADMIN ROUTES
// Route::middleware(['auth:sanctum', 'admin'])->group(function () {


//route to upload an import
Route::post('/imports', [ImportController::class, 'importExcelData']);

//route to update an import
Route::put('/imports/{id}', [ImportController::class, 'update']);

//route to delete an import
Route::delete('/imports/{id}', [ImportController::class, 'destroy']);

Route::post('/fiscalize/{invoice}', [ElifController::class, 'fiscalize']);//route to fiscalize


// });





//we have the login for our project and we access the login for elif while we fiscalize
// Route::get('/login', action: [ElifController::class, 'login']);
