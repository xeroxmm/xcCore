<?php

namespace AppBundle\Crawler;

use AppBundle\AppBundle;
use AppBundle\Safety\Types\Hoster;
use AppBundle\Safety\Web\URLParser;
use AppBundle\Safety\Web\WebsiteInfoGfycat;
use AppBundle\Safety\Web\WebsiteInfoImgur;
use AppBundle\Safety\Web\WebsiteInfoMotherless;
use AppBundle\Safety\Web\WebsiteInfoPornHub;
use AppBundle\Safety\Web\WebsiteInfoStock;
use AppBundle\Safety\Web\WebsiteInfoSymfonyCoreNetwork;
use AppBundle\Safety\Web\WebsiteInfoYoutube;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;

class Crawler {
    private $url;
    private $filePath;
    private $curl;
    private $isURLaWebsite;
    private $lastExtension = 'nn';

    function __construct(string $url) {
        $this->curl          = new CurlObj($url);
        $this->url           = $url;
        $this->filePath      = '';
        $this->isURLaWebsite = FALSE;
    }

    /**
     * @param EntityManagerInterface $em
     * @return bool
     */
    public function isDoubledVideo(EntityManagerInterface $em):bool{
        $q = $em->createQueryBuilder()
                ->select('v.ID')
                ->from('AppBundle:Video','v')
                ->where('v.URL = :url')
                ->setParameter('url', $this->url)
                ->getQuery();

        $q = $q->getResult();

        return (!empty($q));
    }

    public function crwImage(string $crawlDir): bool {
        $this->normalizeCrawlURL();
        $this->curl->parseBinaryToFile();
        return $this->copyTMPPointerToFile($this->curl->getFilePointer(), $crawlDir);
    }

    function __destruct() {
        if(!empty($this->filePath) && file_exists($this->filePath))
            unlink($this->filePath);
    }

    public function getFilePath(): string {
        return $this->filePath;
    }

    private function copyTMPPointerToFile($pointer, string $dir): bool {
        if (!$pointer)
            return FALSE;

        $uri      = stream_get_meta_data($pointer)['uri'];
        $fileName = '/' . ltrim(basename($uri) . time() . '-' . mt_rand(100, 999) . '.tmp', '/') . '.' . $this->curl->getLastExtension();

        $this->filePath = $dir . $fileName;

        $fp = fopen(rtrim($dir, '/') . $fileName, 'w+');
        rewind($pointer);
        while (!feof($pointer)) {
            fwrite($fp, fread($pointer, 1024));
        }
        fclose($fp);
        fclose($pointer);

        return TRUE;
    }

    /** @var  WebsiteInfoStock */
    private $subCrawl;

    private function normalizeCrawlURL() {
        $s = new URLParser($this->url);

        if ($s->isKnownSite() && $s->isNotExtension(['jpg','jpeg','gif','png'])) {
            if ($s->getHoster() == Hoster::TYPE_MOTHERLESS) {
                $sk = new WebsiteInfoMotherless($this->curl->useProxy()->parseTextToVar());
            } else if ($s->getHoster() == Hoster::TYPE_YOUTUBE) {
                $sk = new WebsiteInfoYoutube($this->curl->useProxy()->parseTextToVar());
            } else if ($s->getHoster() == Hoster::TYPE_GFYCAT) {
                $sk = new WebsiteInfoGfycat($this->curl->useProxy()->parseTextToVar());
            } else if ($s->getHoster() == Hoster::TYPE_IMGUR_GIFV) {
                $sk = new WebsiteInfoImgur($this->curl->useProxy()->parseTextToVar(), $this->url);
            } else if ($s->getHoster() == Hoster::TYPE_PORNHUB) {
                $sk = new WebsiteInfoPornHub($this->curl->useProxy()->parseTextToVar());
            } else if ($s->getHoster() >= 100 && $s->getHoster() < 200) {
                $this->curl->setURLPart(1, 'interncrawl');
                $sk = new WebsiteInfoSymfonyCoreNetwork($this->curl->parseTextToVarWithGETParameter(['intern' => 'radegast']));
            } else {
                /*
                 * Fingers crossed that thwe given URL is an IMAGE
                 */
                $sk = new WebsiteInfoStock();
                $sk->setThumbnailURL( $this->url );
            }
            if (!empty($sk->getThumbnailURL())) {
                $this->subCrawl = $sk;
                $this->curl->setURL($sk->getThumbnailURL());
            }
        } else {
            /*
             * Fingers crossed that the given URL is an IMAGE
             */
            $sk = new WebsiteInfoStock();
            $sk->setThumbnailURL( $this->url );
            $this->subCrawl = $sk;
            $this->curl->setURL($sk->getThumbnailURL());
        }
        return;
    }

    /** @return null | WebsiteInfoStock */
    public function getSubCrawl() {
        return $this->subCrawl;
    }
}