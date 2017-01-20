<?php

namespace Platform\RestBundle\Services;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Doctrine\ORM\EntityManager;

class ParameterService
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
     * @param array|null $include
     * @return array
     */
    public function processIncludes($include = [])
    {
        if ((!isset($include)) || empty($include))
        {
            return [];
        }

        $result = [];

        foreach ($include as $prefix => $suffixes)
        {
            $suffixes = explode(',', $suffixes);

            foreach ($suffixes as $suffix)
            {
                if ($suffix != 'full')
                {
                    $result[] = $prefix . $this->mb_ucfirst($suffix);
                } else {
                    $result[] = 'full' . $this->mb_ucfirst($prefix);
                }
            }
        }

       return $result;
    }

    /**
     * mb_ucfirst
     *
     * @param string $str
     * @return string
     */
    public function mb_ucfirst($str)
    {
        return mb_strtoupper(mb_substr($str, 0, 1, 'UTF-8'), 'UTF-8') . mb_substr($str, 1, mb_strlen($str) - 1, 'UTF-8');
    }
}