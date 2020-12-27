<?php

use Illuminate\Support\Facades\Route;

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

Route::get('/', "App\Http\Controllers\AmocrmController@index");

Route::get('/redirect-uri', "App\Http\Controllers\AmocrmController@redirectUri");
Route::get('/get-leads', "App\Http\Controllers\AmocrmController@getLeads")->name("amocrm.get-leads");
Route::post('/send-test-lead', "App\Http\Controllers\AmocrmController@sendTestLead")->name("amocrm.send-test-lead");
