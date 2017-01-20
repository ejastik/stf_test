<?php

namespace Test\MediaBundle\Services;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Doctrine\ORM\EntityManager;
use Test\MediaBundle\Entity\Image;
use Test\MediaBundle\Entity\ImageTag;

class MediaJSONService
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
     * @param Image $entity
     * @param array $include
     * @param boolean|null $useGrapeLoad
     * @return array|null
     */
    public function toJSON(Image $entity, $include = [], $useGrapeLoad = null)
    {
        if (!$entity)
        {
            return null;
        }

//        $useGrapeLoad = $useGrapeLoad ?? ($this->container->hasParameter('use_grape_load') ? $this->container->getParameter('use_grape_load') : true);

        $result = [
            'id' => $entity->getId(),
            'links' => $this->container->get('media.service')->generateImageURL($entity),
            'purpose' => null,
            'tags' => null,
        ];


        if ($this->isIncluded('imagePurpose', $include))
        {
            $result['purpose'] = $entity->getPurpose();
        }

        if ($this->isIncluded('imageTags', $include))
        {
            $tags = $entity->getTags();
            $data = [];

            /** @var ImageTag $tag */
            foreach ($tags as $tag)
            {
                if ($tag->getTag())
                {
                    $data[] = $this->container->get('tag_json.service')->toJSON($tag->getTag());
                }
            }

            $result['tags'] = $data;
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
        return ((in_array($section, $include)) || (in_array('fullImage', $include)));
    }
}