<?php

namespace Test\MediaBundle\Services;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Doctrine\ORM\EntityManager;
use Test\MediaBundle\Entity\Image;
use Test\MediaBundle\Entity\ImageTag;
use Test\MediaBundle\Entity\Tag;

class TagJSONService
{
    protected $container;

    /**
     * __construct
     *
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @param Tag $entity
     * @param array $include
     * @param boolean|null $useGrapeLoad
     * @return array|null
     */
    public function toJSON(Tag $entity, $include = [], $useGrapeLoad = null)
    {
        if (!$entity)
        {
            return null;
        }

//        $useGrapeLoad = $useGrapeLoad ?? ($this->container->hasParameter('use_grape_load') ? $this->container->getParameter('use_grape_load') : true);

        $result = [
            'id' => $entity->getId(),
            'name' => $entity->getName(),
            'images' => null,
        ];

        if ($this->isIncluded('tagImages', $include))
        {
            $images = $entity->getImages();
            $data = [];

            /** @var ImageTag $image */
            foreach ($images as $image)
            {
                if ($image->getImage())
                {
                    $data[] = $this->container->get('media_json.service')->toJSON($image->getImage());
                }
            }

            $result['images'] = $data;
        }

        return $result;
    }

    /**
     * @param string $section
     * @param array $include
     * @return bool
     */
    private function isIncluded($section, $include)
    {
        return ((in_array($section, $include)) || (in_array('fullTag', $include)));
    }
}