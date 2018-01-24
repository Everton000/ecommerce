<?php
/**
 * Created by PhpStorm.
 * User: Everton
 * Date: 24/01/2018
 * Time: 09:21
 */

namespace Hcode;


class PageAdmin extends Page
{
    public function __construct(array $opts = array(), $tpl_dir = "/views/admin/")
    {
        parent::__construct($opts, $tpl_dir);
    }
}