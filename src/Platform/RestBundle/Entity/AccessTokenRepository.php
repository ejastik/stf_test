<?php

namespace Platform\RestBundle\Entity;

/**
 * AccessTokenRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class AccessTokenRepository extends \Doctrine\ORM\EntityRepository
{
    /**
     * @return AccessToken[]
     */
    public function getExpiredTokens()
    {
        return $this->getEntityManager()
            ->createQueryBuilder()
            ->select('e')
            ->from($this->_entityName, 'e')
            ->where('e.expiresAt < :now')
            ->setParameter('now', time())
            ->getQuery()
            ->getResult();
    }
}