<?php

use \Hcode\PageAdmin;
use \Hcode\Model\Product;
use \Hcode\Model\User;

$app->get("/admin/products", function ()
{
    User::verifyLogin();

    $search = (isset($_GET['search'])) ? $_GET['search'] : '';

    $pages = (isset($_GET['page'])) ? (int)$_GET['page'] : 1;

    $pagination = Product::getPage($pages, $search);

    $orderPages = [];

    for ($x = 1; $x < $pagination[2]; $x++)
    {
        array_push($orderPages, array(
            'href' => '/admin/products?' . http_build_query(array(
                    'page' => $x,
                    'search' => $search
                )),
            'text' => $x
        ));
    }

    $page = new PageAdmin();

    $page->setTpl("products", array(
        "products" => $pagination[0],
        "search" => $search,
        "pages" => $orderPages
    ));
});

$app->get("/admin/products/create", function ()
{
    User::verifyLogin();

    $page = new PageAdmin();

    $page->setTpl("products-create");
});

$app->post("/admin/products/create", function ()
{
    User::verifyLogin();

    $product = new Product();

    $product->setData($_POST);

    $product->save();

    header("Location: /admin/products");
    exit;
});

$app->get("/admin/products/:idproduct", function ($idproduct)
{
    User::verifyLogin();

    $product = new Product();

    $product->get((int)$idproduct);

    $page = new PageAdmin();

    $page->setTpl("products-update", array(
        "product" => $product->getValues()
    ));
});

$app->post("/admin/products/:idproduct", function ($idproduct)
{
    User::verifyLogin();

    $product = new Product();

    $product->get((int)$idproduct);

    $product->setData($_POST);

    $product->save();

    $product->setPhoto($_FILES["file"]);

    header("Location: /admin/products");
    exit;
});

$app->get("/admin/products/:idproduct/delete", function ($idproduct)
{
    User::verifyLogin();

    $product = new Product();

    $product->get((int)$idproduct);

    $product->delete();

    header("Location: /admin/products");
    exit;
});
