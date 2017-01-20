<?php

namespace Test\MediaBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * ImageTag
 *
 * @ORM\Table(name="testmediabundle_image_tag")
 * @ORM\Entity(repositoryClass="Test\MediaBundle\Entity\ImageTagRepository")
 * @Gedmo\SoftDeleteable(fieldName="deletedAt", timeAware=false)
 */
class ImageTag
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
     * @ORM\ManyToOne(targetEntity="Test\MediaBundle\Entity\Image", inversedBy="tags", fetch="EAGER")
     * @Assert\NotNull()
     */
    private $image;

    /**
     * @ORM\ManyToOne(targetEntity="Test\MediaBundle\Entity\Tag", inversedBy="images", fetch="EAGER")
     * @Assert\NotNull()
     */
    private $tag;

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
        return [];
    }

    /**
     * Get additional parsers
     *
     * @return array
     */
    public function getAdditionalParsers()
    {
        return [];
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
     * Set deletedAt
     *
     * @param \DateTime $deletedAt
     *
     * @return ImageTag
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
     * Set image
     *
     * @param \Test\MediaBundle\Entity\Image $image
     *
     * @return ImageTag
     */
    public function setImage(\Test\MediaBundle\Entity\Image $image = null)
    {
        $this->image = $image;

        return $this;
    }

    /**
     * Get image
     *
     * @return \Test\MediaBundle\Entity\Image
     */
    public function getImage()
    {
        return $this->image;
    }

    /**
     * Set tag
     *
     * @param \Test\MediaBundle\Entity\Tag $tag
     *
     * @return ImageTag
     */
    public function setTag(\Test\MediaBundle\Entity\Tag $tag = null)
    {
        $this->tag = $tag;

        return $this;
    }

    /**
     * Get tag
     *
     * @return \Test\MediaBundle\Entity\Tag
     */
    public function getTag()
    {
        return $this->tag;
    }
}
