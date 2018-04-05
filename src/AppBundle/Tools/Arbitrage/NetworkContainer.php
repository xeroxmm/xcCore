<?php
namespace  AppBundle\Tools\Arbitrage;

class NetworkContainer {
    private $name = '';
    private $ID = '';

    public function __construct(string $networkName, string $networkID) {
        $this->name = $networkName;
        $this->ID = $networkID;
    }
    public function getNetworkURLString():string{
        return $this->getNetworkURLStringByType();
    }
    public function getNetworkName():string {
        return $this->name;
    }
    public function getNetworkID():string {
        return $this->ID;
    }
    private function getNetworkURLStringByType():string {
        switch($this->name){
            case 'plugrush':
                $string = 'http://imgsmash.com/?pr';
                return $string;
                break;
            default:
                return '';
        }
    }
}