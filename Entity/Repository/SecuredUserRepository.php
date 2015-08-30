<?php
/**
 * Date: 28.08.15
 *
 * @author Portey Vasil <portey@gmail.com>
 */

namespace Youshido\SecurityUserBundle\Entity\Repository;


use Doctrine\ORM\EntityRepository;

class SecuredUserRepository extends EntityRepository
{

    public function findByEmail($email)
    {
        return $this->createQueryBuilder('u')
            ->where('u.email = :email')
            ->andWhere('u.active = :active')
            ->setParameters([
                'email' => $email,
                'active' => true
            ])
            ->getQuery()
            ->getOneOrNullResult();
    }

}