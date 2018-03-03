<?php

use \Hcode\PageAdmin;
use \Hcode\Model\Category;
use \Hcode\Model\User;
use \Hcode\Model\Product;


$app->get("/admin/categories", function()
{
    User::verifyLogin();

    $search = (isset($_GET['search'])) ? $_GET['search'] : '';

    $pages = (isset($_GET['page'])) ? (int)$_GET['page'] : 1;

    $pagination = Category::getPage($pages, $search);

    $orderPages = [];

    for ($x = 1; $x < $pagination[2]; $x++)
    {
        array_push($orderPages, array(
            'href' => '/admin/categories?' . http_build_query(array(
                    'page' => $x,
                    'search' => $search
                )),
            'text' => $x
        ));
    }

    $page = new PageAdmin();

    $page->setTpl("categories", [
        "categories" => $pagination[0],
        "search" => $search,
        "pages" => $orderPages
    ]);
});

$app->get("/admin/categories/create", function ()
{
    User::verifyLogin();

    $page = new PageAdmin();

    $page->setTpl("categories-create");
});

$app->post("/admin/categories/create", function ()
{
    User::verifyLogin();

    $category = new Category();

    $category->setData($_POST);

    $category->save();

    header("Location: /admin/categories");
    exit;
});

$app->get("/admin/categories/:idcategory/delete", function ($idcategory)
{
    User::verifyLogin();

    $category = new Category();

    $category->get((int)$idcategory);

    $category->delete();

    header("Location: /admin/categories");
    exit;
});

$app->get("/admin/categories/:idcategory", function ($idcategory)
{
    User::verifyLogin();

    $category = new Category();

    $category->get((int)$idcategory);

    $page = new PageAdmin();

    $page->setTpl("categories-update", array(
        "category" => $category->getValues()
    ));
});

$app->post("/admin/categories/:idcategory", function ($idcategory)
{
    User::verifyLogin();

    $category = new Category();

    $category->get((int)$idcategory);

    $category->setData($_POST);

    $category->save();

    header("Location: /admin/categories");
    exit;
});

$app->get("/admin/categories/:idcategory/products", function ($idcategory)
{
    User::verifyLogin();

    $category = new Category();

    $category->get((int)$idcategory);

    $page = new PageAdmin();

    $page->setTpl("categories-products", array(
        "category" => $category->getValues(),
        "productsRelated" => $category->getProducts(),
        "productsNotRelated" => $category->getProducts(false)
    ));
});
$app->get("/admin/categories/:idcategory/products/:idproduct/add", function ($idcategory, $idproduct)
{
    User::verifyLogin();

    $category = new Category();

    $category->get((int)$idcategory);

    $product = new Product();

    $product->get((int)$idproduct);

    $category->addProduct($product);

    header("Location: /admin/categories/" . $idcategory . "/products");
    exit;
});
$app->get("/admin/categories/:idcategory/products/:idproduct/remove", function ($idcategory, $idproduct)
{
    User::verifyLogin();

    $category = new Category();

    $category->get((int)$idcategory);

    $product = new Product();

    $product->get((int)$idproduct);

    $category->removeProduct($product);

    header("Location: /admin/categories/" . $idcategory . "/products");
    exit;
});
