<?php

namespace App\Documents;

use DateTime;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Mapping\ClassMetadata;

/**
 * Post document.
 *
 * @ODM\Document(collection="posts")
 */
class Post
{
    /** @ODM\Id */
    private $id;

    /** @ODM\Field(type="string") */
    private $imageName;

    /** @ODM\Field(type="string") */
    private $author;

    /** @ODM\Field(type="collection") */
    private $tags = [];

    /** @ODM\Field(type="int") */
    private $likes = 0;

    /** @ODM\Field(type="date") */
    private $createdAt;

    private $image;

    static public function loadValidatorMetadata(ClassMetadata $metadata)
    {
        $metadata->addPropertyConstraint('author', new Assert\NotBlank());
        $metadata->addPropertyConstraint('author', new Assert\Type(['type' => 'string']));
        $metadata->addPropertyConstraint('author', new Assert\Length(['min' => 2, 'max' => 255]));

        $metadata->addPropertyConstraint('image', new Assert\NotBlank());
        $metadata->addPropertyConstraint('image', new Assert\Image([
            'maxSize' => '2M',
            'mimeTypes' => ['image/gif', 'image/jpeg', 'image/pjpeg', 'image/png', 'image/x-png'],
        ]));
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function getImageName()
    {
        return $this->imageName;
    }

    /**
     * @param string $imageName
     */
    public function setImageName($imageName)
    {
        $this->imageName = $imageName;
    }

    /**
     * @return string
     */
    public function getAuthor()
    {
        return $this->author;
    }

    /**
     * @param string $author
     */
    public function setAuthor($author)
    {
        $this->author = $author;
    }

    /**
     * @return string[]
     */
    public function getTags()
    {
        return $this->tags;
    }

    /**
     * @param mixed $tags
     */
    public function setTags($tags)
    {
        if (null === $tags) {
            return;
        }
        if (!is_array($tags)) {
            $tags = preg_split('/\W+/', (string)$tags);
        }
        $this->tags = array_values(array_filter(array_map(function($tag) {
            return preg_replace('/\W/', '', $tag);
        }, $tags)));
    }

    /**
     * @return int
     */
    public function getLikes()
    {
        return $this->likes;
    }

    public function like()
    {
        ++$this->likes;
    }

    /**
     * @return DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * @param DateTime $createdAt
     */
    public function setCreatedAt(DateTime $createdAt)
    {
        $this->createdAt = $createdAt;
    }

    /**
     * @param UploadedFile $image
     */
    public function setImage(UploadedFile $image = null)
    {
        $this->image = $image;
    }

    /**
     * @return UploadedFile
     */
    public function getImage()
    {
        return $this->image;
    }
}