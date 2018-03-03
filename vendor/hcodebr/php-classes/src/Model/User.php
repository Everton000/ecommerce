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
    const ERROR = "UserError";
    const ERROR_REGISTER = "UserErrorRegister";
    const SUCCESS = "UserSuccess";

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

        if (
            !isset($_SESSION[User::SESSION])
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

        $results = $sql->select("SELECT * FROM tb_users u 
        INNER JOIN tb_persons p ON (p.idperson = u.idperson) WHERE deslogin = :LOGIN", array(
            ":LOGIN" => $login
        ));


        if (count($results) === 0)
        {
            throw new \Exception("Usuário inexistente ou senha inválida", 1);
        }
        $data = $results[0];

        //Verificaçao de senha nao Encriptada
//        if ($password == $data["despassword"])
//        {
//            $user = new User();
//
//            $user->setData($data);
//
//            $_SESSION[User::SESSION] = $user->getValues();
//
//            return $user;
//        }

        //Verificaçao de senha Encriptada
        if (password_verify($password, $data["despassword"]) === true)
        {

            $user = new User();

            $data['desperson'] = utf8_encode($data['desperson']);

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
            if($inadmin)
                header("Location: /admin/login");
            else
                header("Location: /login");

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
            ":desperson" => utf8_decode($this->getdesperson()),
            ":deslogin" => $this->getdeslogin(),
            ":despassword" => User::getPasswordHash($this->getdespassword()),
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
            ":desperson" => utf8_decode($this->getdesperson()),
            ":deslogin" => $this->getdeslogin(),
            ":despassword" => User::getPasswordHash($this->getdespassword()),
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

    public static function getForgot($email, $inadmin = true)
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

                if ($inadmin === true) {
                    $link = "http://www.hcodecommerce.com.br/admin/forgot/reset?code=$code";
                } else {
                    $link = "http://www.hcodecommerce.com.br/forgot/reset?code=$code";
                }

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

    public static function setMsgError($msg)
    {
        $_SESSION[User::ERROR] = $msg;
    }

    public static function getMsgError()
    {
        $msg = isset($_SESSION[User::ERROR]) ? $_SESSION[User::ERROR] : '';

        User::clearMsgError();

        return $msg;
    }

    public static function clearMsgError()
    {
        $_SESSION[User::ERROR] = NULL;
    }

    public static function setMsgErrorRegister($msg)
    {
        $_SESSION[User::ERROR_REGISTER] = $msg;
    }

    public static function getMsgErrorRegister()
    {
        $msg = isset($_SESSION[User::ERROR_REGISTER]) ? $_SESSION[User::ERROR_REGISTER] : '';

        User::clearMsgErrorRegister();

        return $msg;
    }

    public static function clearMsgErrorRegister()
    {
        $_SESSION[User::ERROR_REGISTER] = NULL;
    }

    public static function getPasswordHash($password)
    {
        return password_hash($password, PASSWORD_DEFAULT, ['cost' => 12]);
    }

    public static function checkLoginExist($login)
    {
        $sql = new Sql();

        $results = $sql->select("SELECT * FROM tb_users WHERE deslogin = :login", array(
            ":login" => $login
        ));

        return (count($results) > 0);
    }

    public static function setSuccess($msg)
    {
        $_SESSION[User::SUCCESS] = $msg;
    }

    public static function getSuccess()
    {
        $msg = isset($_SESSION[User::SUCCESS]) ? $_SESSION[User::SUCCESS] : '';

        User::clearSuccess();

        return $msg;
    }

    public static function clearSuccess()
    {
        $_SESSION[User::SUCCESS] = NULL;
    }

    public function getOrders()
    {
        $sql = new Sql();

        $results = $sql->select("SELECT * FROM tb_orders o 
                                INNER JOIN tb_ordersstatus s USING(idstatus)
                                INNER JOIN tb_carts c USING (idcart)
                                INNER JOIN tb_users u ON (u.iduser = o.iduser)
                                INNER JOIN tb_addresses a USING (idaddress)
                                INNER JOIN tb_persons p ON (p.idperson = u.idperson)
                                WHERE u.iduser = :iduser", array(
            ":iduser" => $this->getiduser()
        ));

        return $results;
    }

    public static function getPage($page = 1, $search = '', $itensPerPage = 10)
    {
        $start = ($page - 1) * $itensPerPage;

        $sql = new Sql();

        if ($search == '')
        {
            $results = $sql->select("SELECT SQL_CALC_FOUND_ROWS *
                                FROM tb_users a  
                                INNER JOIN tb_persons b USING(idperson)
                                ORDER BY b.desperson
                                LIMIT $start, $itensPerPage
                                ");
        } else {

            $results = $sql->select("SELECT SQL_CALC_FOUND_ROWS *
                                FROM tb_users a  
                                INNER JOIN tb_persons b USING(idperson)
                                WHERE b.desperson LIKE :search OR b.desemail = :search OR a.deslogin LIKE :search
                                ORDER BY b.desperson
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