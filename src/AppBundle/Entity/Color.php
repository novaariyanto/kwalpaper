<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\Common\Collections\ArrayCollection;
/**
 * Color
 *
 * @ORM\Table(name="color_table")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\ColorRepository")
 */
class Color
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
     *
     * @ORM\Column(name="title", type="string", length=255)
     * @Assert\NotBlank()
     * @Assert\Length(
     *      min = 3,
     *      max = 200,
     * )
     */
    private $title;

    /**
     * @var string
     *
     * @ORM\Column(name="code", type="string", length=255)
     * @Assert\NotBlank()
     * @Assert\Length(
     *      min = 6,
     *      max = 6,
     * )
     */
    private $code;

    /**
     * @var int
     *
     * @ORM\Column(name="position", type="integer")
     */
    private $position;

    /**
     * @var bool
     *
     * @ORM\Column(name="enabled", type="boolean")
     */
    private $enabled;

    /**
     * @ORM\ManyToMany(targetEntity="Wallpaper" ,mappedBy="colors")
     * @ORM\OrderBy({"created" = "desc"})
     */
    private $wallpapers;

    public function __construct()
    {
        $this->wallpapers = new ArrayCollection();
        $this->enabled = true;
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
     * @return Color
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
     * Set code
     *
     * @param string $code
     * @return Color
     */
    public function setCode($code)
    {
        $this->code = $code;

        return $this;
    }

    /**
     * Get code
     *
     * @return string 
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * Set position
     *
     * @param integer $position
     * @return Color
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
     * Set enabled
     *
     * @param boolean $enabled
     * @return Color
     */
    public function setEnabled($enabled)
    {
        $this->enabled = $enabled;

        return $this;
    }

    /**
     * Get enabled
     *
     * @return boolean 
     */
    public function getEnabled()
    {
        return $this->enabled;
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
    public function getColor()
    {
        return $this;
    }
}
