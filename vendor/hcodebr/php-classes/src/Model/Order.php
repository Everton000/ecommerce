<?php
/**
 * Created by PhpStorm.
 * User: Everton
 * Date: 24/02/2018
 * Time: 16:14
 */

namespace Hcode\Model;

use \Hcode\DB\Sql;
use \Hcode\Model;

class Order extends Model
{
    const SESSION = "Order";
    const ERROR = "OrderError";
    const SUCCESS = "OrderSuccess";

    public function save()
    {
        $sql = new Sql();

        $results = $sql->select("CALL sp_orders_save(:idorder, :idcart, :iduser, :idstatus, :idaddress, :vltotal)", array(
            ":idorder" => $this->getidorder(),
            ":idcart" => $this->getidcart(),
            ":iduser" => $this->getiduser(),
            ":idstatus" => $this->getidstatus(),
            ":idaddress" => $this->getidaddress(),
            ":vltotal" => $this->getvltotal()
        ));

        if ($results[0] > 0)
            $this->setData($results[0]);

    }
    public function get($idorder)
    {
        $sql = new Sql();

        $results = $sql->select("SELECT * FROM tb_orders o 
                                INNER JOIN tb_ordersstatus s ON (s.idstatus = o.idstatus)
                                INNER JOIN tb_carts c ON (c.idcart = o.idcart)
                                INNER JOIN tb_users u ON (u.iduser = o.iduser)
                                INNER JOIN tb_addresses a ON (a.idaddress = o.idaddress)
                                INNER JOIN tb_persons p ON (p.idperson = u.idperson)
                                WHERE o.idorder = :idorder", array(
                                ":idorder" => $idorder
        ));
        if (count($results[0]) > 0)
            $this->setData($results[0]);

    }

    public static function listAll()
    {
        $sql = new Sql();

        $results = $sql->select("SELECT * FROM tb_orders o 
                                INNER JOIN tb_ordersstatus s ON (s.idstatus = o.idstatus)
                                INNER JOIN tb_carts c ON (c.idcart = o.idcart)
                                INNER JOIN tb_users u ON (u.iduser = o.iduser)
                                INNER JOIN tb_addresses a ON (a.idaddress = o.idaddress)
                                INNER JOIN tb_persons p ON (p.idperson = u.idperson)
                                ORDER BY o.dtregister DESC");
        return $results;
    }

    public function delete()
    {
        $sql = new Sql();

        $sql->query("DELETE FROM tb_orders WHERE idorder = :idorder", array(
            ":idorder" => $this->getidorder()
        ));
    }

    public function getCart():Cart
    {
        $cart = new Cart();

        $cart->get((int)$this->getidcart());

        return $cart;
    }

    public static function setMsgError($msg)
    {
        $_SESSION[Order::ERROR] = $msg;
    }

    public static function getMsgError()
    {
        $msg = isset($_SESSION[Order::ERROR]) ? $_SESSION[Order::ERROR] : '';

        Order::clearMsgError();

        return $msg;
    }

    public static function clearMsgError()
    {
        $_SESSION[Order::ERROR] = NULL;
    }

    public static function setSuccess($msg)
    {
        $_SESSION[Order::SUCCESS] = $msg;
    }

    public static function getSuccess()
    {
        $msg = isset($_SESSION[Order::SUCCESS]) ? $_SESSION[Order::SUCCESS] : '';

        Order::clearSuccess();

        return $msg;
    }

    public static function clearSuccess()
    {
        $_SESSION[Order::SUCCESS] = NULL;
    }

    public static function getPage($page = 1, $search = '', $itensPerPage = 10)
    {
        $start = ($page - 1) * $itensPerPage;

        $sql = new Sql();

        if ($search == '')
        {
            $results = $sql->select("SELECT SQL_CALC_FOUND_ROWS *
                                FROM tb_orders o 
                                INNER JOIN tb_ordersstatus s ON (s.idstatus = o.idstatus)
                                INNER JOIN tb_carts c ON (c.idcart = o.idcart)
                                INNER JOIN tb_users u ON (u.iduser = o.iduser)
                                INNER JOIN tb_addresses a ON (a.idaddress = o.idaddress)
                                INNER JOIN tb_persons p ON (p.idperson = u.idperson)
                                ORDER BY o.dtregister DESC
                                LIMIT $start, $itensPerPage
                                ");
        } else {

            $results = $sql->select("SELECT SQL_CALC_FOUND_ROWS *
                                FROM tb_orders o 
                                INNER JOIN tb_ordersstatus s ON (s.idstatus = o.idstatus)
                                INNER JOIN tb_carts c ON (c.idcart = o.idcart)
                                INNER JOIN tb_users u ON (u.iduser = o.iduser)
                                INNER JOIN tb_addresses a ON (a.idaddress = o.idaddress)
                                INNER JOIN tb_persons p ON (p.idperson = u.idperson)
                                WHERE o.idorder = :id OR p.desperson LIKE :search
                                ORDER BY o.dtregister DESC 
                                LIMIT $start, $itensPerPage
                                ", array(
                ":search" => '%'. $search .'%',
                ":id" => $search
            ));
        }

        $total = $sql->select("SELECT FOUND_ROWS() AS nrtotal");

        $pages = ceil($total[0]["nrtotal"] / $itensPerPage);

        return array ($results, (int)$total, $pages);
    }
}