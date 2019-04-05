<?php

namespace AppBundle\Entity;
use MediaBundle\Entity\Media;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
/**
 * Category
 *
 * @ORM\Table(name="category_table")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\CategoryRepository")
 */
class Category
{ 
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;
    /**
     * @var string
     * @Assert\NotBlank()
     * @Assert\Length(
     *      min = 3,
     *      max = 25,
     * )
     * @ORM\Column(name="title", type="string", length=255))
     */
    private $title;

    /**
     * @var int
     *
     * @ORM\Column(name="position", type="integer")
     */
    private $position;

    /**
     * @ORM\ManyToMany(targetEntity="Wallpaper"  ,mappedBy="categories")
     * @ORM\OrderBy({"created" = "desc"})
     */
    private $wallpapers;
    /**
     * @ORM\ManyToOne(targetEntity="MediaBundle\Entity\Media")
     * @ORM\JoinColumn(name="media_id", referencedColumnName="id")
     * @ORM\JoinColumn(nullable=false)
     */
    private $media;
    /**
     * @Assert\File(mimeTypes={"image/jpeg","image/png" },maxSize="20M")
     */
    private $file;


     /**
     * @Assert\NotNull()
     * @ORM\ManyToOne(targetEntity="Section", inversedBy="categories")
     * @ORM\JoinColumn(name="section_id", referencedColumnName="id", nullable=false)
     */
    private $section;
    public function __construct()
    {
        $this->wallpapers = new ArrayCollection();
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
     * Set title
     *
     * @param string $title
     * @return Category
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get title
     *
     * @return string 
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set position
     *
     * @param integer $position
     * @return Category
     */
    public function setPosition($position)
    {
        $this->position = $position;

        return $this;
    }

    /**
     * Get position
     *
     * @return integer 
     */
    public function getPosition()
    {
        return $this->position;
    }



        /**
     * Add Wallpapers
     *
     * @param Wallpaper $wallpapers
     * @return Categorie
     */
    public function addWallpaper(Wallpaper $wallpapers)
    {
        $this->wallpapers[] = $wallpapers;

        return $this;
    }

    /**
     * Remove Wallpapers
     *
     * @param Wallpaper $wallpapers
     */
    public function removeWallpaper(Wallpaper $wallpapers)
    {
        $this->wallpapers->removeElement($wallpapers);
    }

    /**
     * Get Wallpapers
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getWallpapers()
    {
        return $this->wallpapers;
    }
        public function __toString()
    {
        return $this->title;
    }
    public function getCategory()
    {
        return $this;
    }
         /**
     * Set media
     *
     * @param string $media
     * @return Article
     */
    public function setMedia(Media $media)
    {
        $this->media = $media;

        return $this;
    }

    /**
     * Get media
     *
     * @return string 
     */
    public function getMedia()
    {
        return $this->media;
    }
    public function getFile()
    {
        return $this->file;
    }
    public function setFile($file)
    {
        $this->file = $file;
        return $this;
    }
    
              /**
     * Set section
     *
     * @param integer $section
     * @return section
     */
    public function setSection(Section $section)
    {
        $this->section = $section;

        return $this;
    }

    /**
     * Get section
     *
     * @return integer 
     */
    public function getSection()
    {
        return $this->section;
    }
}
