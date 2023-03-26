<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Auth::routes();

Route::group(['prefix' => 'admin', ], function () {
    Route::get('/login', 'Auth\Admin\LoginController@showAdminLoginForm');
    Route::post('/login', 'Auth\Admin\LoginController@login');
    Route::post('/logout', 'Auth\Admin\LoginController@logout')->name('admin.logout');

    Route::group(['middleware' => 'auth:admin', ], function () {
        //后台认证路由

    });
});
Route::get('/home', 'HomeController@index')->name('home');