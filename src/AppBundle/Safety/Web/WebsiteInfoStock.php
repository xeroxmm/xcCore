<?php

namespace AppBundle\Safety\Web;

class WebsiteInfoStock {
    protected $title        = '';
    protected $description  = '';
    protected $tags         = [];
    protected $genres       = [];
    protected $categories   = [];
    protected $ID           = '';
    protected $thumbnailURL = '';
    protected $user         = '';
    protected $timeCrated   = 0;
    protected $views        = 0;
    protected $length       = 0;
    protected $rateUp       = 0;
    protected $rateDown     = 0;
    protected $comments     = 0;
    protected $overwriteType = 0;
    /**
     * @return string
     */
    public function getTitle(): string {
        return $this->title;
    }
    public function hasOverwrittenType():bool {
        return $this->overwriteType != 0;
    }
    public function getOverwrittenType():int {
        return $this->overwriteType;
    }
    /**
     * @return string
     */
    public function getDescription(): string {
        return $this->description;
    }

    /**
     * @return array
     */
    public function getTags(): array {
        return $this->tags;
    }

    /**
     * @return array
     */
    public function getGenres(): array {
        return $this->genres;
    }

    /**
     * @return array
     */
    public function getCategories(): array {
        return $this->categories;
    }

    /**
     * @return string
     */
    public function getID(): string {
        return $this->ID;
    }

    /**
     * @return string
     */
    public function getThumbnailURL(): string {
        return $this->thumbnailURL;
    }

    public function setThumbnailURL(string $url) {
        $this->thumbnailURL = $url;
    }

    /**
     * @return string
     */
    public function getUser(): string {
        return $this->user;
    }

    /**
     * @return int
     */
    public function getTimeCrated(): int {
        return $this->timeCrated;
    }

    /**
     * @return int
     */
    public function getViews(): int {
        return $this->views;
    }

    /**
     * @return int
     */
    public function getLength(): int {
        return $this->length;
    }

    /**
     * @return int
     */
    public function getRateUp(): int {
        return $this->rateUp;
    }

    /**
     * @return int
     */
    public function getRateDown(): int {
        return $this->rateDown;
    }

    /**
     * @return int
     */
    public function getComments(): int {
        return $this->comments;
    }


}