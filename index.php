<?php
session_start();

require_once("vendor/autoload.php");

use \Slim\Slim;

$app = new Slim();

$app->config('debug', true);

require_once("functions/Utils.php");
require_once("index-rotas/site.php");
require_once("index-rotas/admin.php");
require_once("index-rotas/admin-users.php");
require_once("index-rotas/admin-catogories.php");
require_once("index-rotas/admin-products.php");
require_once("index-rotas/admin-orders.php");

$app->run();

 ?>