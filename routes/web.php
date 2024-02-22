<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\AccountController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// Route::get('/', function () {
//     return view('welcome');
// });

Route::get('/',[HomeController::class,'index'])->name('home');

Route::group(['account'], function(){

    // Guest Route
    Route::group(['middlewae' => 'guest'], function(){
        Route::get('/registe',[AccountController::class,'registration'])->name('account.registration');
        Route::post('/proces-register',[AccountController::class,'processRegistration'])->name('account.processRegistration');
        Route::get('/login',[AccountController::class,'login'])->name('account.login');
        Route::post('/authenticate',[AccountController::class,'authenticate'])->name('account.authenticate');
    });

    // Authenticated Routes -> If user tries to access the profile page or guest routes without login directly then it will redirect user to the login page
    Route::group(['middlewae' => 'auth'], function(){
        Route::get('/profile',[AccountController::class,'profile'])->name('account.profile');
        Route::put('/update-profile',[AccountController::class,'updateProfile'])->name('account.updateProfile');
        Route::get('/logout',[AccountController::class,'logout'])->name('account.logout'); 
        Route::post('/update-profile-pic',[AccountController::class, 'updateProfilePic'])->name('account.updateProfilePic');      
    });
});