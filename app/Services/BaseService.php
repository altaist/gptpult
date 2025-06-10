<?php

namespace App\Services;

abstract class BaseService
{
    /**
    * @return static
    */
   public static function make()
   {
       return app(static::class);
   }
}
