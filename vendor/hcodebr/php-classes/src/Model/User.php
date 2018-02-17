<?php

namespace Hcode\Model;

use Hcode\DB\Sql;
use Hcode\Mailer;
use Hcode\Model;
use Rain\Tpl\Exception;

class User Extends Model
{
    const SESSION = "User";
    const SECRET = "HcodePhp7_Secret";

    public static function getFromSession()
    {
        $user = new User();

        if(isset($_SESSION[User::SESSION]) && (int)$_SESSION[User::SESSION]['iduser'] > 0)
            $user->setData($_SESSION[User::SESSION]);

        return $user;
    }

    //Verifica o status do login
    public static function checkLogin($inadmin = true)
    {

        if (!isset($_SESSION[User::SESSION])
        ||
        !$_SESSION[User::SESSION]
        ||
        !(int)$_SESSION[User::SESSION]["iduser"] > 0 )
        {
            //Não está logado
            return false;
        } else {

            if($inadmin === true && (bool)$_SESSION[User::SESSION]["inadmin"] === true)
            {
                //Está logado e é um admin
                return true;

            } else if ($inadmin === false)
            {
                //Está logado mas não é um admin
                return true;
            } else {
                //Não está logado
                return false;
            }
        }
    }

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

        /*//Verificaçao de senha nao Encriptada
        if ($password == $data["despassword"])
        {
            $user = new User();

            $user->setData($data);

            $_SESSION[User::SESSION] = $user->getValues();

            return $user;
        }*/

        //Verificaçao de senha Encriptada
        if (password_verify($password, $data["despassword"]) === true)
        {

            $user = new User();

            $user->setData($data);

            $_SESSION[User::SESSION] = $user->getValues();

            return $user;
        }
        else {

            throw new \Exception("Usuário inexistente ou senha inválida", 1);
        }
    }

    public static function verifyLogin($inadmin = true)
    {
        if (User::checkLogin($inadmin) === false)
        {
            header("Location: /admin/login");
            exit;
        }
    }

    public static function logout()
    {
        $_SESSION[User::SESSION] = NULL;
    }

    public static function listAll()
    {
        $sql = new Sql();

        return $sql->select("SELECT * FROM tb_users a  INNER JOIN tb_persons b USING(idperson) ORDER BY b.desperson");
    }

    public function save()
    {
        $sql = new Sql();

        $results = $sql->select("CALL sp_users_save(:desperson, :deslogin, :despassword, :desemail, :nrphone, :inadmin)", array(
            ":desperson" => $this->getdesperson(),
            ":deslogin" => $this->getdeslogin(),
            ":despassword" => $this->getdespassword(),
            ":desemail" => $this->getdesemail(),
            ":nrphone" => $this->getnrphone(),
            ":inadmin" => $this->getinadmin()
        ));

        $this->setData($results[0]);

    }

    public function get($iduser)
    {
        $sql = new Sql();

        $dados = $sql->select(
            "SELECT * FROM tb_users 
                    INNER JOIN tb_persons ON (tb_persons.idperson = tb_users.idperson)
                    WHERE tb_users.iduser = :iduser ",
            array(
            ":iduser" => $iduser
        ));

        $this->setData($dados[0]);

    }

    public function update()
    {
        $sql = new Sql();

        $results = $sql->select("CALL sp_usersupdate_save(:iduser, :desperson, :deslogin, :despassword, :desemail, :nrphone, :inadmin)", array(
            ":iduser" => $this->getiduser(),
            ":desperson" => $this->getdesperson(),
            ":deslogin" => $this->getdeslogin(),
            ":despassword" => $this->getdeslogin(),
            ":desemail" => $this->getdesemail(),
            ":nrphone" => $this->getnrphone(),
            ":inadmin" => $this->getinadmin()
        ));

        $this->setData($results[0]);
    }

    public function delete()
    {
        $sql = new Sql();

        $sql->query("CALL sp_users_delete(:iduser)", array(
            ":iduser" => $this->getiduser()
        ));

    }

    public static function getForgot($email)
    {
        $sql = new Sql();

        $results = $sql->select("
            SELECT * FROM tb_persons
            INNER JOIN tb_users ON (tb_users.idperson = tb_persons.idperson)
            WHERE tb_persons.desemail = :email;
            ", array(
                ":email" => $email
            ));

        if(count($results) === 0)
        {
            throw new \Exception("Não foi possível recuperar a senha.", 1);
        }
        else
        {
            $data = $results[0];

            $results2 = $sql->select("CALL sp_userspasswordsrecoveries_create(:iduser, :desip)", array(
                ":iduser" => $data['iduser'],
                ":desip" => $_SERVER['REMOTE_ADDR']
            ));

            if(count($results2) === 0)
            {
                throw new \Exception("Não foi possível recuperar a senha.");
            }
            else
            {
                $dataRecovery = $results2[0];

                $code = base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_128, User::SECRET, $dataRecovery['idrecovery'], MCRYPT_MODE_ECB));

                $link = "http://www.hcodecommerce.com.br/admin/forgot/reset?code=$code";

                $mailer = new Mailer($data['desemail'], $data['desperson'], "Redefinir senha da Hcode Store", "forgot",
                    array(
                        "name" => $data['desperson'],
                        "link" => $link
                    ));

                $mailer->send();

                return $data;

            }
        }
    }

    public static function validForgotDecrypt($code)
    {
        $idRecovery = mcrypt_decrypt(MCRYPT_RIJNDAEL_128, User::SECRET, base64_decode($code), MCRYPT_MODE_ECB);

        $sql = new Sql();

        $results = $sql->select("SELECT * FROM tb_userspasswordsrecoveries
                    INNER JOIN tb_users ON (tb_users.iduser = tb_userspasswordsrecoveries.iduser)
                    INNER JOIN tb_persons ON (tb_persons.idperson = tb_users.idperson)
                    WHERE tb_userspasswordsrecoveries.idrecovery = :idrecovery
                    AND dtrecovery IS NULL
                    AND DATE_ADD(tb_userspasswordsrecoveries.dtregister, INTERVAL 1 HOUR) >= NOW();
                    ", array(
                        ":idrecovery" => $idRecovery
        ));
        if(count($results) === 0)
        {
            throw new Exception("Nao foi possível recuperar a senha.", 1);
        }
        else
            {
            return $results[0];
        }
    }

    public static function setFogotUsed($idrecovery)
    {
        $sql = new Sql();

        $sql->query("UPDATE tb_userspasswordsrecoveries SET dtrecovery = NOW() WHERE idrecovery = :idrecovery", array(
            ":idrecovery" => $idrecovery
        ));
    }

    public function setPassword($password)
    {
        $sql = new Sql();

        $sql->query("UPDATE tb_users SET despassword = :password WHERE iduser = :iduser", array(
            ":password" => $password,
            ":iduser" => $this->getiduser()
        ));
    }

}