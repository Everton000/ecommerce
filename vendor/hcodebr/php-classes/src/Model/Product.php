<?php
/**
 * Created by PhpStorm.
 * User: Everton
 * Date: 13/02/2018
 * Time: 13:09
 */

namespace Hcode\Model;

use Hcode\DB\Sql;
use Hcode\Model;
use Hcode\Mailer;
use Rain\Tpl\Exception;

class Product extends Model
{

    public static function listAll()
    {
        $sql = new Sql();

        return $sql->select("SELECT * FROM tb_products ORDER BY desproduct");
    }

    public static function checkList($list)
    {
        foreach ($list as &$row)
        {
            $p = new Product();
            $p->setData($row);
            $row = $p->getValues();

        }
        return $list;
    }

    public function save()
    {
        $sql = new Sql();

        $results = $sql->select("CALL sp_products_save(:idproduct, :desproduct, :vlprice, :vlwidth, :vlheight, :vllength, :vlweight, :desurl)", array(
            ":idproduct" => $this->getidproduct(),
            ":desproduct" => $this->getdesproduct(),
            ":vlprice" => $this->getvlprice(),
            ":vlwidth" => $this->getvlwidth(),
            ":vlheight" => $this->getvlheight(),
            ":vllength" => $this->getvllength(),
            ":vlweight" => $this->getvlweight(),
            ":desurl" => $this->getdesurl()
        ));

        $this->setData($results[0]);
    }

    public function get($idproduct)
    {
        $sql = new Sql();

        $results = $sql->select("SELECT * FROM tb_products WHERE idproduct = :idproduct", array(
            ":idproduct" => $idproduct
        ));

        $this->setData($results[0]);
    }

    public function delete()
    {
        $sql = new Sql();

        $sql->query("DELETE FROM tb_products WHERE idproduct = :idproduct", array(
            ":idproduct" => $this->getidproduct()
        ));
    }

    public function checkPhoto()
    {
        if (file_exists(
            $_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR .
            "res" . DIRECTORY_SEPARATOR .
            "site" . DIRECTORY_SEPARATOR .
            "img" . DIRECTORY_SEPARATOR .
            "products" . DIRECTORY_SEPARATOR .
            $this->getidproduct() . ".jpg"))
        {
            $url =  "/res/site/img/products/" . $this->getidproduct() . ".jpg";
        } else {

            $url = "/res/site/img/product.jpg";

        }
        return $this->setdesphoto($url);
    }

    public function getValues()
    {
        $this->checkPhoto();

        $values = parent::getValues(); // TODO: Change the autogenerated stub

        return $values;
    }

    public function setPhoto($file)
    {
        $extension = explode('.', $file["name"]);
        $extension = end($extension);

        switch ($extension)
        {
            case "jpg":
            case "jpeg":
                $image = imagecreatefromjpeg($file["tmp_name"]);

                break;
            case "git":
                $image = imagecreatefromgif($file["tmp_name"]);

                break;
            case "png":
                $image = imagecreatefrompng($file["tmp_name"]);

                break;
        }
        $dist = $_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR .
            "res" . DIRECTORY_SEPARATOR .
            "site" . DIRECTORY_SEPARATOR .
            "img" . DIRECTORY_SEPARATOR .
            "products" . DIRECTORY_SEPARATOR .
            $this->getidproduct() . ".jpg";

        imagejpeg($image, $dist);

        imagedestroy($image);

        $this->checkPhoto();
    }

    //Método que busca um produto pela sua URL (parte de detalhes)
    public function getFromURL($desurl)
    {
        $sql = new Sql();

        $results = $sql->select("SELECT * FROM tb_products WHERE desurl = :desurl LIMIT 1", array(
            ":desurl" => $desurl
        ));

        $this->setData($results[0]);
    }

    //Método que busca a categoria de um produto (parte de detalhes)
    public function getCategories()
    {
        $sql = new Sql();

        return $sql->select("SELECT * FROM tb_categories 
                            INNER JOIN tb_productscategories ON (tb_productscategories.idcategory = tb_categories.idcategory)
                            WHERE tb_productscategories.idproduct = :idproduct", array(
            ":idproduct" => $this->getidproduct()
        ));
    }

    //Lista com paginação.
    public static function getPage($page = 1, $search = '', $itensPerPage = 10)
    {
        $start = ($page - 1) * $itensPerPage;

        $sql = new Sql();

        if ($search == '')
        {
            $results = $sql->select("SELECT SQL_CALC_FOUND_ROWS *
                                FROM tb_products ORDER BY desproduct
                                LIMIT $start, $itensPerPage
                                ");
        } else {

            $results = $sql->select("SELECT SQL_CALC_FOUND_ROWS *
                                FROM tb_products
                                WHERE desproduct LIKE :search
                                ORDER BY desproduct 
                                LIMIT $start, $itensPerPage
                                ", array(
                ":search" => '%'. $search .'%'
            ));
        }

        $total = $sql->select("SELECT FOUND_ROWS() AS nrtotal");

        $pages = ceil($total[0]["nrtotal"] / $itensPerPage);

        return array ($results, (int)$total, $pages);
    }
}