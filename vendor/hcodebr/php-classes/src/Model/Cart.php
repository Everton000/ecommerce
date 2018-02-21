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
use Slim\Http\Util;


class Cart extends Model
{
    const SESSION = "carts";
    const SESSION_ERROR = "CartError";

    //busca a sessão do carrinho
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

    //seta nova sessão do carrinho
    public function setToSession()
    {
        $_SESSION[Cart::SESSION] = $this->getValues();
    }

    //busca a sessão pelo dessessionid
    public function getFromSessionID()
    {
        $sql = new Sql();

        $results = $sql->select("SELECT * FROM tb_carts WHERE dessessionid = :dessessionid", array(
            ":dessessionid" => session_id()
        ));

        if (count($results) > 0)
            $this->setData($results[0]);
    }

    //busca um carrinho pelo id
    public function get($idcart)
    {
        $sql = new Sql();

        $results = $sql->select("SELECT * FROM tb_carts WHERE idcart = :idcart", array(
            ":idcart" => $idcart
        ));

        if (count($results) > 0)
            $this->setData($results[0]);
    }

    //adiciona ou edita um carrinho de compras
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

    //adiciona um produto
    public function addProduct(Product $product)
    {
        $sql = new Sql();

        $sql->query("INSERT INTO tb_cartsproducts (idcart, idproduct) VALUES (:idcart, :idproduct)", array(
            ":idcart" => $this->getidcart(),
            ":idproduct" => $product->getidproduct()
        ));

        $this->getCalculateTotal();

    }

    //remove um produto ou mais produtos
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

        $this->getCalculateTotal();
    }

    //busca produtos que estão em um carrinho de compra
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

    //busca produtos que estão em um carrinho de compra e sua somatória para calcular o frete
    public function getProductsTotals()
    {
        $sql = new Sql();

        $results = $sql->select("SELECT 
                                SUM(vlprice) AS vlprice, 
                                SUM(vlwidth) AS vlwidth,
                                SUM(vlheight) AS vlheight,
                                SUM(vllength) AS vllength,
                                SUM(vlweight) AS vlweight,
                                COUNT(*) AS nrqtd
                                    FROM tb_products 
                                INNER JOIN tb_cartsproducts ON (tb_cartsproducts.idproduct = tb_products.idproduct)
                                WHERE tb_cartsproducts.idcart = :idcart AND dtremoved IS NULL", array(
            ":idcart" => $this->getidcart()
        ));

        if(count($results[0]) > 0)
            return $results[0];
        else
            return [];
    }

    public function setFreight($nrzipcode)
    {
        $nrzipcode = str_replace("-", "", $nrzipcode);

        $totals = $this->getProductsTotals();

        if($totals['nrqtd'] > 0)
        {
            if($totals['vlheight'] < 2) $totals['vlheight'] = 2;
            if($totals['vlheight'] < 16) $totals['vllength'] = 16;
            if($totals['vllength'] < 16) $totals['vllength'] = 16;

            $qs = http_build_query(array(
                'nCdEmpresa' => '',
                'sDsSenha' => '',
                'nCdServico' => '40010',
                'sCepOrigem' => '09853120',
                'sCepDestino' => $nrzipcode,
                'nVlPeso' => $totals['vlweight'],
                'nCdFormato' => '1',
                'nVlComprimento' => $totals['vllength'],
                'nVlAltura' => $totals['vlheight'],
                'nVlLargura' => $totals['vlwidth'],
                'nVlDiametro' => '0',
                'sCdMaoPropria' => 'S',
                'nVlValorDeclarado' => $totals['vlprice'],
                'sCdAvisoRecebimento' => 'S'
            ));

            $xml = simplexml_load_file("http://ws.correios.com.br/calculador/CalcPrecoPrazo.asmx/CalcPrecoPrazo?" . $qs);

            $result = $xml->Servicos->cServico;

            if ($result['MsgErro'] != '')
            {
                Cart::setMsgError($result['MsgErro']);
            } else {

                Cart::clearMsgError();

            }

            $this->setnrdays($result->PrazoEntrega);
            $this->setvlfreight(Cart::formatValueToDecimal($result->Valor));
            $this->setdeszipcode($nrzipcode);

            $this->save();

            return $result;

        } else {
            //quando não houver mais itens no carrinho zeramos os campos
            $this->setnrdays(0);
            $this->setvlfreight(0.00);
            $this->setdeszipcode('');

            $this->save();

        }
    }

    //Formata o valor para inserir no banco
    public static function formatValueToDecimal($value):float
    {
        $value = str_replace(".", "", $value);
        return str_replace(",", ".", $value);
    }

    public static function setMsgError($msg)
    {
        $_SESSION[Cart::SESSION_ERROR] = $msg;
    }

    public static function getMsgError()
    {
        $msg = isset($_SESSION[Cart::SESSION_ERROR]) ? $_SESSION[Cart::SESSION_ERROR] : '';

        Cart::clearMsgError();

        return $msg;
    }

    public static function clearMsgError()
    {
        $_SESSION[Cart::SESSION_ERROR] = NULL;
    }

    public function updateFreight()
    {
        if($this->getdeszipcode() != '')
        {
            $this->setFreight($this->getdeszipcode());
        }
    }

    public function getValues()
    {
        $this->getCalculateTotal();

        return parent::getValues(); // TODO: Change the autogenerated stub
    }

    public function getCalculateTotal()
    {
        $this->updateFreight();

        $totals = $this->getProductsTotals();

        $this->setvlsubtotal($totals['vlprice']);
        $this->setvltotal($totals['vlprice'] + $this->getvlfreight());
    }
}