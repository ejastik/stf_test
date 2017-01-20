<?php

namespace Platform\RestBundle\Services;

use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Doctrine\ORM\EntityManager;

class HydrationService
{
    protected $container;
    protected $em;

    /**
     * __construct
     *
     * @param ContainerInterface $container
     * @param EntityManager $entityManager
     */
    public function __construct(ContainerInterface $container, EntityManager $entityManager)
    {
        $this->container = $container;
        $this->em = $entityManager;
    }



    /**
     * Returns getter for given field or null if field is inaccessible.
     * Checks, does it exists and is it allowed to access.
     *
     * @param $entity
     * @param string $field
     * @return null|string
     */
    public function getGetter($entity, $field)
    {
        if ((!$entity) || (gettype($entity) != 'object'))
        {
            return null;
        }

        $getter = 'get' . $this->mb_ucfirst($field);

        if (
            (method_exists($entity, $getter)) &&
            !(
                (method_exists($entity, 'getNotAccessibleFields')) &&
                (in_array($field, $entity->getNotAccessibleFields()))
            )
        )
        {
            return $getter;
        } else {
            $getter = 'is' . $this->mb_ucfirst($field);

            if (
                (method_exists($entity, $getter)) &&
                !(
                    (method_exists($entity, 'getNotAccessibleFields')) &&
                    (in_array($field, $entity->getNotAccessibleFields()))
                )
            )
            {
                return $getter;
            }
        }

        return null;
    }

    /**
     * Returns setter for given field or null if field is inaccessible.
     * Checks, does it exists and is it allowed to access.
     *
     * @param $entity
     * @param string $field
     * @return null|string
     */
    public function getSetter($entity, $field)
    {
        if ((!$entity) || (gettype($entity) != 'object'))
        {
            return null;
        }

        $getter = 'set' . $this->mb_ucfirst($field);

        if (
            (method_exists($entity, $getter)) &&
            !(
                (method_exists($entity, 'getNotAccessibleFields')) &&
                (in_array($field, $entity->getNotAccessibleFields()))
            )
        )
        {
            return $getter;
        }

        return null;
    }

    /**
     * mb_ucfirst
     *
     * @param string $str
     * @return string
     */
    public function mb_ucfirst($str)
    {
        $words = explode('_', str_replace('-', '_', $str));

        foreach ($words as &$word)
        {
            $word = mb_strtoupper(mb_substr($word, 0, 1, 'UTF-8'), 'UTF-8') . mb_substr($word, 1, mb_strlen($word) - 1, 'UTF-8');
        }

        return implode('', $words);
    }

}