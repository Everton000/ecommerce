<?php
/**
 * Created by PhpStorm.
 * User: Everton
 * Date: 15/02/2018
 * Time: 09:41
 */

namespace Hcode\Model;

use Hcode\DB\Sql;
use Hcode\Model;
use Hcode\Model\User;
use Hcode\Mailer;
use Rain\Tpl\Exception;



class Cart extends Model
{
    const SESSION = "carts";

    public static function getFromSession()
    {
        $cart = new Cart();

        if (isset($_SESSION[Cart::SESSION]) && (int)$_SESSION[Cart::SESSION]['idcart'] > 0)
        {
            $cart->get((int)$_SESSION[Cart::SESSION]['idcart']);
        } else {

            $cart->getFromSessionID();

            if(!(int)$cart->getidcart() > 0)
            {
                $data = array(
                    "dessessionid" => session_id()
                );

                //Se está logado
                if(User::checkLogin(false) === true)
                {
                    $user = User::getFromSession();

                    $data['iduser'] = $user->getiduser();
                }

                $cart->setData($data);

                $cart->save();

                $cart->setToSession();
            }
        }
        return $cart;
    }

    public function setToSession()
    {
        $_SESSION[Cart::SESSION] = $this->getValues();
    }

    public function getFromSessionID()
    {
        $sql = new Sql();

        $results = $sql->select("SELECT * FROM tb_carts WHERE dessessionid = :dessessionid", array(
            ":dessessionid" => session_id()
        ));

        if (count($results) > 0)
            $this->setData($results[0]);
    }

    public function get($idcart)
    {
        $sql = new Sql();

        $results = $sql->select("SELECT * FROM tb_carts WHERE idcart = :idcart", array(
            ":idcart" => $idcart
        ));

        if (count($results) > 0)
            $this->setData($results[0]);
    }

    public function save()
    {
        $sql = new Sql();
        $results = $sql->select("CALL sp_carts_save(:idcart, :dessessionid, :iduser, :deszipcode, :vlfreight, :nrdays)", array(
            ":idcart" => $this->getidcart(),
            ":dessessionid" => $this->getdessessionid(),
            ":iduser" => $this->getiduser(),
            ":deszipcode" => $this->getdeszipcode(),
            ":vlfreight" => $this->getvlfreight(),
            ":nrdays" => $this->getnrdays()
        ));

        $this->setData($results[0]);
    }

    public function addProduct(Product $product)
    {
        $sql = new Sql();

        $sql->query("INSERT INTO tb_cartsproducts (idcart, idproduct) VALUES (:idcart, :idproduct)", array(
            ":idcart" => $this->getidcart(),
            ":idproduct" => $product->getidproduct()
        ));
    }

    public function removeProduct(Product $product, $all = false)
    {
        $sql = new Sql();

        if($all === true)
        {
            //remove todos os produtos do mesmo id
            $sql->query("UPDATE tb_cartsproducts SET dtremoved = NOW() 
                        WHERE idcart = :idcart AND idproduct = :idproduct
                        AND dtremoved IS NULL ", array(
                ":idcart" => $this->getidcart(),
                ":idproduct" => $product->getidproduct()
            ));
        } else {

            //remove somente um produto por vez
            $sql->query("
                        UPDATE tb_cartsproducts SET dtremoved = NOW() 
                        WHERE idcart = :idcart AND idproduct = :idproduct 
                        AND dtremoved IS NULL 
                        LIMIT 1", array(
                ":idcart" => $this->getidcart(),
                ":idproduct" => $product->getidproduct()
            ));
        }
    }

    public function getProducts()
    {
        $sql = new Sql();

        $rows = $sql->select("
                  SELECT p.idproduct, p.desproduct, p.vlprice, p.vlwidth, p.vlheight, p.vllength, p.vlweight, p.desurl,
              COUNT(*) AS nrqtd, SUM(p.vlprice) AS vltotal
              FROM tb_cartsproducts  c
              INNER JOIN tb_products p ON(p.idproduct = c.idproduct)
              WHERE c.idcart = :cart
              AND c.dtremoved IS NULL 
              GROUP BY p.idproduct, p.desproduct, p.vlprice, p.vlwidth, p.vlheight, p.vllength, p.vlweight,  p.desurl
              ORDER BY p.desproduct", array(
                  ":cart" => $this->getidcart()
        ));

        return Product::checkList($rows);
    }
}