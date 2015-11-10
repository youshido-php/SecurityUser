<?php
/**
 * Date: 10.11.15
 *
 * @author Portey Vasil <portey@gmail.com>
 */

namespace Youshido\SecurityUserBundle\Service;


use Symfony\Component\DependencyInjection\ContainerAware;
use Youshido\SecurityUserBundle\Entity\SecuredUser;

class UserProvider extends ContainerAware
{

    /**
     * @param $id
     * @return SecuredUser
     */
    public function findUserById($id)
    {
        return $this->container->get('doctrine')->getRepository($this->getUserClass())
            ->find($id);
    }

    public function getUserClass()
    {
        return $this->container->getParameter('youshido_security_user.model');
    }

    /**
     * @param $email
     * @return SecuredUser
     */
    public function findUserByEmail($email)
    {
        return $this->container->get('doctrine')->getRepository($this->getUserClass())
            ->findOneBy(['email' => $email]);
    }

    /**
     * @return SecuredUser
     */
    public function createNewUserInstance()
    {
        $userClass = $this->getUserClass();

        return new $userClass;
    }

    public function activateUser(SecuredUser $user, $withFlush = true)
    {
        $user->setActive(true);
        $user->setActivatedAt(new \DateTime());

        if ($withFlush) {
            $this->container->get('doctrine')->getManager()->persist($user);
            $this->container->get('doctrine')->getManager()->flush();
        }
    }

    public function generateUserPassword(SecuredUser &$user, $password = '')
    {
        $encoder = $this->container->get('security.password_encoder');

        if (!$password) {
            $password = md5(uniqid() . time());
        }


        $user->setPassword($encoder->encodePassword($user, $password));
    }
}