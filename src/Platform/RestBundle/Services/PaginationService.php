<?php

namespace Platform\RestBundle\Services;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Doctrine\ORM\EntityManager;

class PaginationService
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
     * @param $page
     * @param $limit
     * @return array
     */
    public function getPagination($page, $limit)
    {
        $pagination = ($this->container->hasParameter('pagination')) ? $this->container->getParameter('pagination') : [];
        $default_limit = $pagination['default_limit'] ?? 10;
        $max_limit = $pagination['max_limit'] ?? 100;

        $page = $page ?? 1;
        $limit = $limit ?? $default_limit;
        $errors = [];

        if ((isset($page)) && (!preg_match('/^[1-9][0-9]*$/', $page)))
        {
//            $errors[] = 'Page must be a positive integer';
            $errors[] = 'Страница должна быть положительным числом';
            $page = 1;
        }

        if ((isset($limit)) && (!preg_match('/^[1-9][0-9]*$/', $limit)))
        {
//            $errors[] = 'Limit must be a positive integer';
            $errors[] = 'Лимит должен быть положительным числом';
            $limit = 0;
        }

        if ($limit > $max_limit)
        {
//            $errors[] = 'Limit cannot be more than ' . $max_limit;
            $errors[] = 'Лимит не может быть больше ' . $max_limit;
        }

        return [($page - 1) * $limit, (integer)$limit, $errors];
    }

    /**
     * @param $entities
     * @param $offset
     * @param $limit
     * @param $totalRecords
     * @return array
     */
    public function generatePagination($entities, $offset, $limit, $totalRecords)
    {
        return [
            'records' => count($entities),
            'page' => (ceil($offset / $limit) + 1),
            'limit' => $limit,
            'totalPages' => ceil($totalRecords / $limit),
            'totalRecords' => $totalRecords,
        ];
    }
}