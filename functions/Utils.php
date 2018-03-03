<?php

/**
 * Created by PhpStorm.
 * User: Everton
 * Date: 13/02/2018
 * Time: 17:51
 */

use \Hcode\Model\User;
use \Hcode\Model\Cart;

class Utils
{
    //Formata o valor para mostrar na tela
    public static function formatPrice($vlprice)
    {
        if(!$vlprice > 0)
            $vlprice = 0;

        return number_format($vlprice, 2, ",", ".");
    }

    //Formata o valor para inserir no banco
    public static function formatValueToDecimal($value):float
    {
        $value = str_replace(".", "", $value);
        return str_replace(",", ".", $value);
    }

    public static function formatDate($date)
    {
        return date('d/m/Y', strtotime($date));
    }

    public static function checkLogin($inadmin = true)
    {
        return User::checkLogin($inadmin);
    }

    public static function getUserName()
    {
        $user = User::getFromSession();

        return $user->getdesperson();
    }

    public static function format($string)
    {
        return utf8_encode($string);
    }

    public static function getCartNrQtd()
    {
        $cart = Cart::getFromSession();

        $totals = $cart->getProductsTotals();

        return $totals['nrqtd'];
    }

    public static function getCartVlSubTotal()
    {
        $cart = Cart::getFromSession();

        $totals = $cart->getProductsTotals();

        return self::formatPrice($totals['vlprice']);
    }


}