<?php

namespace Test\MediaBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Gedmo\Mapping\Annotation as Gedmo;
use Platform\RestBundle\Validator\Constraints\URL as URL;

/**
 * Image
 *
 * @ORM\Table(name="testmediabundle_image")
 * @ORM\Entity(repositoryClass="Test\MediaBundle\Entity\ImageRepository")
 * @Gedmo\SoftDeleteable(fieldName="deletedAt", timeAware=false)
 */
class Image
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="url", type="string", nullable=false, options={"default" = "image"})
     * @Assert\NotNull()
     * @Assert\NotBlank()
     * @URL
     */
    private $url;

    /**
     * @var string
     *
     * @ORM\Column(name="purpose", type="string", nullable=true)
     */
    private $purpose;

    /**
     * @var string
     *
     * @ORM\Column(name="image_type", type="string", nullable=true)
     */
    private $imageType;

    /**
     * @ORM\OneToMany(targetEntity="Test\MediaBundle\Entity\ImageTag", mappedBy="image", cascade={"persist", "remove"})
     */
    private $tags;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="deletedAt", type="datetime", nullable=true)
     */
    private $deletedAt;

    /**
     * To string conversion for forms.
     *
     * @return string
     */
    public function __toString()
    {
        return (string)$this->id;
    }

    /**
     * Get not accessible fields.
     *
     * @return array
     */
    public function getNotAccessibleFields()
    {
        return ['url', 'imageType'];
    }

    /**
     * Get additional parsers
     *
     * @return array
     */
    public function getAdditionalParsers()
    {
        return [
            [
                'service' => 'media.service',
                'method' => 'generateImageUrl',
                'field' => null,
                'only_for_entities' => [],
                'not_for_entities' => [],
            ],
        ];
    }
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->tags = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set url
     *
     * @param string $url
     *
     * @return Image
     */
    public function setUrl($url)
    {
        $this->url = $url;

        return $this;
    }

    /**
     * Get url
     *
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Set purpose
     *
     * @param string $purpose
     *
     * @return Image
     */
    public function setPurpose($purpose)
    {
        $this->purpose = $purpose;

        return $this;
    }

    /**
     * Get purpose
     *
     * @return string
     */
    public function getPurpose()
    {
        return $this->purpose;
    }

    /**
     * Set imageType
     *
     * @param string $imageType
     *
     * @return Image
     */
    public function setImageType($imageType)
    {
        $this->imageType = $imageType;

        return $this;
    }

    /**
     * Get imageType
     *
     * @return string
     */
    public function getImageType()
    {
        return $this->imageType;
    }

    /**
     * Set deletedAt
     *
     * @param \DateTime $deletedAt
     *
     * @return Image
     */
    public function setDeletedAt($deletedAt)
    {
        $this->deletedAt = $deletedAt;

        return $this;
    }

    /**
     * Get deletedAt
     *
     * @return \DateTime
     */
    public function getDeletedAt()
    {
        return $this->deletedAt;
    }

    /**
     * Add tag
     *
     * @param \Test\MediaBundle\Entity\ImageTag $tag
     *
     * @return Image
     */
    public function addTag(\Test\MediaBundle\Entity\ImageTag $tag)
    {
        $this->tags[] = $tag;

        return $this;
    }

    /**
     * Remove tag
     *
     * @param \Test\MediaBundle\Entity\ImageTag $tag
     */
    public function removeTag(\Test\MediaBundle\Entity\ImageTag $tag)
    {
        $this->tags->removeElement($tag);
    }

    /**
     * Get tags
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getTags()
    {
        return $this->tags;
    }
}
