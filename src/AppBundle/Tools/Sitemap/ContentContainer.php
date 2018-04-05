<?php
namespace AppBundle\Tools\Sitemap;

class ContentContainer {
    private $url;
    private $lastmod;
    private $frequency;
    private $priority;

    public function __construct(string $url) {
        $this->url = $url;
    }

    /**
     * @return bool|string
     */
    public function getUrl() {
        return $this->url ?? FALSE;
    }

    /**
     * @return bool|string
     */
    public function getLastmod() {
        return $this->lastmod ?? FALSE;
    }

    /**
     * @param int $lastmod
     */
    public function setLastmod($lastmod) {
        $this->lastmod = date('Y-m-d', $lastmod);
    }

    /**
     * @return bool|string
     */
    public function getFrequency() {
        return $this->frequency ?? FALSE;
    }

    /**
     * @param string $frequency
     */
    public function setFrequency($frequency) {
        $this->frequency = $frequency;
    }

    /**
     * @return bool|float
     */
    public function getPriority() {
        return $this->priority ?? FALSE;
    }

    /**
     * @param mixed float
     */
    public function setPriority($priority) {
        $this->priority = $priority;
    }

}