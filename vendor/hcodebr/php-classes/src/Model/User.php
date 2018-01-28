<?php

namespace Hcode\Model;

use Hcode\DB\Sql;
use Hcode\Model;

class User Extends Model
{
    const SESSION = "User";

    public static function Login($login, $password)
    {
        $sql = new Sql();

        $results = $sql->select("SELECT * FROM tb_users WHERE deslogin = :LOGIN", array(
            ":LOGIN" => $login
        ));

        if (count($results) === 0)
        {
            throw new \Exception("Usuário inexistente ou senha inválida", 1);
        }
        $data = $results[0];

        //Verificaçao de senha nao Encriptada
        if ($password == $data["despassword"])
        {
            $user = new User();

            $user->setData($data);

            $_SESSION[User::SESSION] = $user->getValues();

            return $user;
        }

        //Verificaçao de senha Encriptada
        /*if (password_verify($password, $data["despassword"]) === true)
        {
            $user = new User();

            $user->setData($data);

            return $user;
        }*/
        else {

            throw new \Exception("Usuário inexistente ou senha inválida", 1);
        }
    }

    public static function verifyLogin($inadmin = true)
    {
        if (
            !isset($_SESSION[User::SESSION])
            ||
            !$_SESSION[User::SESSION]
            ||
            !(int)$_SESSION[User::SESSION]["iduser"] > 0
            ||
            (bool)$_SESSION[User::SESSION]["inadmin"] !== $inadmin
        ) {
            header("Location: /admin/login");
            exit;
        }
    }

    public static function logout()
    {
        $_SESSION[User::SESSION] = NULL;
    }
}