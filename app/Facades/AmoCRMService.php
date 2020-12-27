<?php


namespace App\Facades;


use Illuminate\Support\Facades\Facade;

class AmoCRMService extends Facade
{
    protected static function getFacadeAccessor()
    {
       return "AmoCRMService";
    }
}