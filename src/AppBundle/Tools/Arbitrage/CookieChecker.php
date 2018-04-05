<?php
namespace AppBundle\Tools\Arbitrage;

class CookieChecker {
    private $valueLeyka = '0';
    private $valueLevchenko = '-1';

    public function __construct(\Symfony\Component\HttpFoundation\ParameterBag $cookies) {
        if(empty($cookies->all()))
            return;

        $this->valueLeyka = (int)$cookies->get('_leyka', '0');
        $this->valueLevchenko = (int)$cookies->get('_levchenko','-1');
    }

    public function isSendToAdnetworkEvent():bool {
        return $this->valueLeyka == 1 && $this->valueLevchenko >= 0;
    }
    public function getAdnetworkID():int {
        return $this->valueLevchenko;
    }
}