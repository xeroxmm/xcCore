<?php
namespace  AppBundle\Tools\Arbitrage;

class ConfigContainer {
    private $isEnabled = FALSE;
    /** @var NetworkContainer[]  */
    private $networks = [];

    public function __construct(array $config) {
        $this->isEnabled = (string)($config['use'] ?? '0') == '1' ? TRUE : FALSE;
        $networkz = $config['networks'] ?? NULL;

        if(!$networkz || count($networkz) < 1){
            $this->isEnabled = FALSE;
            return;
        }
        foreach($networkz as $net){
            if(!($net['name'] ?? FALSE) || !($net['id'] ?? FALSE))
                continue;

            $this->networks[$net['name'].'-'.$net['id']] = new NetworkContainer($net['name'], $net['id']);
        }
        if(empty($this->networks))
            $this->isEnabled = FALSE;
    }
    public function isEnabled():bool {
        return $this->isEnabled;
    }
    public function getRandomNetworkURLString():string{
        $keys = array_keys($this->networks);
        $randomNumber = mt_rand(0,count($keys)-1);
        $key = $keys[ $randomNumber ];

        return $this->networks[$key]->getNetworkURLString();
    }
    public function getRandomNetworkID():int{
        $keys = array_keys($this->networks);
        $randomNumber = mt_rand(0,count($keys)-1);

        return $randomNumber;
    }
    public function getNetworkObjectByID(int $ID):NetworkContainer{
        $keys = array_keys($this->networks);
        $key = $keys[ $ID ] ?? 0;

        return $this->networks[$key];
    }
}