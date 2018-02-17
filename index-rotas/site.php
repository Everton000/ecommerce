<?php

use \Hcode\Page;
use \Hcode\Model\Category;
use \Hcode\Model\Product;
use \Hcode\Model\Cart;

$app->get('/', function()
{
    $products = Product::listAll();

    $page = new Page();

    $page->setTpl("index", array(
        "products" => Product::checkList($products)
    ));
});

$app->get("/categories/:idcategory", function ($idcategory)
{
    $page = (isset($_GET['page'])) ? (int)$_GET['page'] : 1;

    $category = new Category();

    $category->get((int)$idcategory);

    $pagination = $category->getProductsPage($page);

    $pages = [];

    for($i = 1; $i <= $pagination[2]; $i++)
    {
        array_push($pages, [
            "link" => '/categories/' . $category->getidcategory() . '?page=' . $i,
            'page' => $i
        ]);
    }

    $page = new Page();

    $page->setTpl("category", array(
        "category" => $category->getValues(),
        "products" => $pagination[0],
        "pages" => $pages
    ));
});

$app->get("/products/:desurl", function($desurl)
{
    $product = new Product();

    $product->getFromURL($desurl);

    $page = new Page();

    $page->setTpl("product-detail", array(
        "product" => $product->getValues(),
        "categories" => $product->getCategories()
    ));
});

$app->get("/cart", function ()
{
    $cart = Cart::getFromSession();

    $page = new Page();

    $page->setTpl("cart", array(
        "cart" => $cart->getValues(),
        "products" => $cart->getProducts()
    ));
});

$app->get("/cart/:idproduct/add", function($idproduct)
{
    $product = new Product();

    $product->get((int)$idproduct);

    $cart = Cart::getFromSession();

    $qtd = (isset($_GET['qtd'])) ? (int)$_GET['qtd'] : 1;

    for ($i = 0; $i < $qtd; $i++)
    {
        $cart->addProduct($product);
    }

    header("Location: /cart");
    exit;
});

$app->get("/cart/:idproduct/minus", function($idproduct)
{
    $product = new Product();

    $product->get((int)$idproduct);

    $cart = Cart::getFromSession();

    $cart->removeProduct($product);

    header("Location: /cart");
    exit;
});

$app->get("/cart/:idproduct/remove", function($idproduct)
{
    $product = new Product();

    $product->get((int)$idproduct);

    $cart = Cart::getFromSession();

    $cart->removeProduct($product,true);

    header("Location: /cart");
    exit;
});