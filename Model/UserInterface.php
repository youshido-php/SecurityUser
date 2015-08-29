<?php

namespace Youshido\SecurityUserBundle\Model;
use Youshido\SecurityUserBundle\Entity\User;

/**
 * Created by PhpStorm.
 * User: portey-virtula
 * Date: 29.08.15
 * Time: 12:43
 */
interface UserInterface
{

    /**
     * @return User
     */
    public function getUser();
}