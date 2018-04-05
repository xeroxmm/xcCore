<?php
namespace AppBundle\API;

class APIError {
    private $code;
    private $errorCode;
    private $status;

    function __construct(&$code){
        $this->code = $code;
        $this->status = FALSE;
        $this->errorCode = '0.0';
    }

    public function noPayload(){
        $this->code = 403;
        $this->errorCode = '403.1 - payload';
        $this->status = TRUE;
    }
    public function noCredentials(){
        $this->code = 403;
        $this->errorCode = '403.2 - credentials';
        $this->status = TRUE;
    }
    public function wrongURL(string $url = ''){
        $this->code = 200;
        $this->errorCode = '500.1' . ((!empty($url)) ? ' - ' . $url : '');
        $this->status = TRUE;
    }
    public function noContentID(){
        $this->code = 200;
        $this->errorCode = '500.20' . ((!empty($url)) ? ' - ' . $url : '');
        $this->status = TRUE;
    }
	public function noDatabaseFin( string $r = ''){
		$this->code = 200;
		$this->errorCode = '500.21' . ((!empty($r)) ? ' - ' . $r : '');
		$this->status = TRUE;
	}
    public function noImageInfo(string $url){
        $this->code = 200;
        $this->errorCode = '500.3' . ((!empty($url)) ? ' - ' . $url : '');
        $this->status = TRUE;
    }
    public function getStatus():bool { return $this->status; }
    public function getErrorString():string { return $this->errorCode; }
}