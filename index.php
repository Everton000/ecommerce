<?php 

require_once("vendor/autoload.php");

use \Slim\Slim;

$app = new Slim();

use \Hcode\Page;

$app->config('debug', true);

$app->get('/', function() {

    $page = new Page();

    $page->setTpl("index");
});

$app->run();

 ?>