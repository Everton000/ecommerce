<?php

use \Hcode\PageAdmin;
use \Hcode\Model\User;

$app->get("/admin/users/:iduser/password", function ($iduser)
{
    User::verifyLogin();

    $user = new User();

    $user->get((int)$iduser);

    $page = new PageAdmin();

    $page->setTpl("users-password", array(
        "user" =>$user->getValues(),
        "msgError" =>$user::getMsgError(),
        "msgSuccess" =>$user::getSuccess()
    ));
});

$app->post("/admin/users/:iduser/password", function ($iduser)
{
    User::verifyLogin();

    if (!isset($_POST['despassword']) || $_POST['despassword'] == '')
    {
        User::setMsgError("Preencha a nova senha.");
        header("Location: /admin/users/$iduser/password");
        exit;
    }
    if (!isset($_POST['despassword-confirm']) || $_POST['despassword-confirm'] == '')
    {
        User::setMsgError("Preencha a confirmação da nova senha.");
        header("Location: /admin/users/$iduser/password");
        exit;
    }

    if ($_POST['despassword'] !== $_POST['despassword-confirm'])
    {
        User::setMsgError("Confirme corretamente as senhas.");
        header("Location: /admin/users/$iduser/password");
        exit;
    }

    $user = new User();

    $user->get((int)$iduser);

    $user->setPassword(User::getPasswordHash($_POST['despassword']));

    User::setSuccess("Senha alterada com sucesso.");
    header("Location: /admin/users/$iduser/password");
    exit;
});

$app->get("/admin/users", function ()
{
    User::verifyLogin();

    $search = (isset($_GET['search'])) ? $_GET['search'] : '';

    $pages = (isset($_GET['page'])) ? (int)$_GET['page'] : 1;

    $pagination = User::getPage($pages, $search);

    $orderPages = [];

    for ($x = 1; $x < $pagination[2]; $x++)
    {
        array_push($orderPages, array(
            'href' => '/admin/users?' . http_build_query(array(
                    'page' => $x,
                    'search' => $search
                )),
            'text' => $x
        ));
    }

    $page = new PageAdmin();

    $page->setTpl("users", array(
        "users" => $pagination[0],
        "search" => $search,
        "pages" => $orderPages
    ));
});

$app->get("/admin/users/create", function ()
{
    User::verifyLogin();

    $page = new PageAdmin();

    $page->setTpl("users-create");
});

$app->get('/admin/users/:iduser/delete', function ($iduser)
{
    User::verifyLogin();

    $user = new User();

    $user->get((int)$iduser);

    $user->delete();

    header("Location: /admin/users");
    exit;
});

$app->get("/admin/users/:iduser", function ($iduser)
{
    User::verifyLogin();

    $user = new User();

    $user->get((int)$iduser);

    $page = new PageAdmin();

    $page->setTpl('users-update', array(
        "user" => $user->getValues()
    ));
});

$app->post('/admin/users/create', function ()
{
    User::verifyLogin();

    $user = new User();

    $_POST["inadmin"] = (isset($_POST["inadmin"])) ? 1 : 0;

    $user->setData($_POST);

    $user->save();

    header("Location: /admin/users");
    exit;

});

$app->post('/admin/users/:iduser', function ($iduser)
{
    User::verifyLogin();

    $user = new User();

    $user->get((int)$iduser);

    $_POST["inadmin"] = (isset($_POST["inadmin"])) ? 1 : 0;

    $user->setData($_POST);

    $user->update();

    header("Location: /admin/users");
    exit;

});