<?php

/**
 * Created by PhpStorm.
 * User: Everton
 * Date: 13/02/2018
 * Time: 17:51
 */
class Utils
{
    public static function formatPrice(float $vlprice)
    {
        return number_format($vlprice, 2, ",", ".");
    }
}