<?php

namespace App\Repository;

use Doctrine\ORM\EntityRepository;
use App\Entity\User;
use App\Entity\ApiToken;

class ApiTokenRepository extends EntityRepository
{
    /**
     * @param $token
     * @return ApiToken
     */
    public function findOneByToken($token)
    {
        return $this->findOneBy(array('token' => $token));
    }

    /**
     * @param User $user
     * @return ApiToken[]
     */
    public function findAllForUser(User $user)
    {
        return $this->findBy(array('user' => $user->getId()));
    }
}
