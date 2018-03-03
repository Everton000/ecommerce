<?php
/**
 * Created by PhpStorm.
 * User: Everton
 * Date: 12/02/2018
 * Time: 13:09
 */

namespace Hcode\Model;

use Hcode\DB\Sql;
use Hcode\Model;
use Hcode\Mailer;
use Rain\Tpl\Exception;


class Category extends Model
{
    public static function listAll()
    {
        $sql = new Sql();

        return $sql->select("SELECT * FROM tb_categories ORDER BY descategory");
    }

    public function save()
    {
        $sql = new Sql();

        $results = $sql->select("CALL sp_categories_save(:idcategory, :descategory)", array(
            ":idcategory" => $this->getidcategory(),
            ":descategory" => $this->getdescategory()
        ));
        $this->setData($results[0]);

        Category::updateFile();
    }

    public function get($idcategory)
    {
        $sql = new Sql();

        $results = $sql->select("SELECT * FROM tb_categories WHERE idcategory = :idcategory", array(
            ":idcategory" => $idcategory
        ));

        $this->setData($results[0]);
    }

    public function delete()
    {
        $sql = new Sql();

        $sql->query("DELETE FROM tb_categories WHERE idcategory = :idcategory", array(
            ":idcategory" => $this->getidcategory()
        ));

        Category::updateFile();
    }

    public function updateFile()
    {
        $categories = Category::listAll();

        $html = [];

        foreach($categories as $row)
        {
            array_push($html, '<li><a href="/categories/' . $row['idcategory'] . '">' . $row['descategory'] . '</a></li>');
        }
        file_put_contents($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . "views" . DIRECTORY_SEPARATOR . "categories-menu.html", implode('', $html));
    }

    public function getProducts($related = true)
    {
        $sql = new Sql();

        if($related === true)
        {
            return $sql->select("SELECT * FROM tb_products WHERE idproduct IN(
                          SELECT tb_products.idproduct FROM tb_products 
                          INNER JOIN tb_productscategories ON (tb_productscategories.idproduct = tb_products.idproduct)
                          WHERE tb_productscategories.idcategory = :idcategory);", array(
                              ":idcategory" => $this->getidcategory()
            ));
        } else {
            return $sql->select("SELECT * FROM tb_products WHERE idproduct NOT IN(
                          SELECT tb_products.idproduct FROM tb_products 
                          INNER JOIN tb_productscategories ON (tb_productscategories.idproduct = tb_products.idproduct)
                          WHERE tb_productscategories.idcategory = :idcategory);", array(
                ":idcategory" => $this->getidcategory()
            ));
        }
    }

    //Busca produtos por categoria em paginação
    public function getProductsPage($page = 1, $itensPerPage = 8)
    {
        $start = ($page - 1) * $itensPerPage;

        $sql = new Sql();

        $results = $sql->select("SELECT SQL_CALC_FOUND_ROWS * FROM tb_products
                      INNER JOIN tb_productscategories ON (tb_productscategories.idproduct = tb_products.idproduct)
                      INNER JOIN tb_categories ON (tb_categories.idcategory = tb_productscategories.idcategory)
                      WHERE tb_categories.idcategory = :idcategory
                      LIMIT $start, $itensPerPage;", array(
                          ":idcategory" => $this->getidcategory(),
        ));
        $total = $sql->select("SELECT FOUND_ROWS() AS nrtotal");

        $pages = ceil($total[0]["nrtotal"] / $itensPerPage);

        return array (Product::checkList($results), (int)$total, $pages);
    }

    public function addProduct(Product $product)
    {
        $sql = new Sql();

        $sql->query("INSERT INTO tb_productscategories (idcategory, idproduct) VALUES (:idcategory, :idproduct)", array(
            ":idcategory" => $this->getidcategory(),
            ":idproduct" => $product->getidproduct()
        ));
    }
    public function removeProduct(Product $product)
    {
        $sql = new Sql();

        $sql->query("DELETE FROM tb_productscategories WHERE idcategory = :idcategory AND idproduct = :idproduct", array(
            ":idcategory" => $this->getidcategory(),
            ":idproduct" => $product->getidproduct()
        ));
    }

    public static function getPage($page = 1, $search = '', $itensPerPage = 10)
    {
        $start = ($page - 1) * $itensPerPage;

        $sql = new Sql();

        if ($search == '')
        {
            $results = $sql->select("SELECT SQL_CALC_FOUND_ROWS *
                                FROM tb_categories ORDER BY descategory
                                LIMIT $start, $itensPerPage
                                ");
        } else {

            $results = $sql->select("SELECT SQL_CALC_FOUND_ROWS *
                                FROM tb_categories 
                                WHERE descategory LIKE :search
                                ORDER BY descategory
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
?>