<?php
namespace AppBundle\Crawler;

class CurlObj {
    private $url;
    private $isCookie;
    private $isSSL;
    private $isOnlyHeader;
    private $isBinary;

    private $header;
    private $referrer;

    /** @var bool | resource */
    private $file;
    private $status;
    private $connectionTimeOut;
    private $error;
    private $lastExtension;
    private $parameterGET;

    function __construct( string $url ) {
        $this->status = TRUE;
        $this->url = $url;
        $this->isCookie = FALSE;
        $this->isSSL = FALSE;
        $this->isOnlyHeader = FALSE;
        $this->isBinary = FALSE;

        $this->header = 'User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_10_3) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/44.0.2403.89 Safari/537.36';
        $this->header = 'BotXYZ';
        $this->referrer = '';
        $this->file = FALSE;
        $this->connectionTimeOut = 15;
        $this->error = 0;
        $parameterGET = [];
    }
    function getURL(){ return $this->url; }
    function setURL ( string $url  ) { $this->url = $url; }
    function getFilePointer(){ return $this->file; }
    function parseBinaryToFile():bool{
        $this->isBinary = TRUE;
        $this->file = @tmpfile();

        return $this->crawlImg();
    }
    function setURLPart(int $part, string $stringToInsert){
        $temp = parse_url($this->url);
        if(isset($temp["path"])){
            $this->url = str_replace($temp["path"],'_#|#_',$this->url);
            $path = explode('/',$temp["path"]);
            if(isset($path[$part]))
                $path[$part] = $stringToInsert;
            $path = implode('/', $path);
            $this->url = str_replace('_#|#_',$path,$this->url);
        }
    }
    function parseTextToVarWithGETParameter(array $parameter){
        $this->parameterGET = $parameter;

        if(!empty($parameter)){
            $this->url .= '?';
            foreach($parameter as $param => $value){
                $this->url .= urlencode($param).'='.urlencode($value).'&';
            }
            $this->url = trim($this->url,' &');
        }

        return $this->crawlText();
    }
    function parseTextToVar():string {
        return $this->crawlText();
    }
    private function __curlWriteFunction($c, $data){
        echo $l = fwrite($this->file, $data);
        return $l;
    }
    private function crawlText():string{
        if($this->useProxy)
            return file_get_contents(
                'http://martin-goerner.com/xmark/prey.php?n80iiobbnmddlduubloiodd11119&url='.urlencode( $this->url )
            );
        $ch = curl_init ($this->url);

        $headerInt = 0;
        $returnInt = 1;

        if($this->isOnlyHeader){
            $headerInt = 1;
            $returnInt = 0;
        }

        $result = parse_url($this->url);

        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);

        // curl_setopt($ch, CURLOPT_VERBOSE, FALSE);
        curl_setopt($ch, CURLOPT_HEADER, $headerInt);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, $returnInt);

        curl_setopt($ch, CURLOPT_REFERER, $result['scheme'].'://'.$result['host']);
        curl_setopt($ch, CURLOPT_USERAGENT, $this->header);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->connectionTimeOut);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);

        curl_setopt($ch, CURLOPT_COOKIESESSION, TRUE);
        curl_setopt($ch, CURLOPT_COOKIEJAR, 'cookie_l45001.txt');

        $s = curl_exec($ch);

        if(($this->error = curl_errno($ch))) {
            curl_close ($ch);
            $this->status = FALSE;
            return '';
        }
        curl_close ($ch);

        $this->status = TRUE;
        return $s;
    }
    public function getLastExtension():string{ return $this->lastExtension; }
    private function crawlImg():bool{
        if($this->isBinary && !$this->file)
            return ($this->status = FALSE);

        $ch = curl_init ($this->url);
        $temp = explode('.',$this->url);
        $temp = $temp[ count($temp) - 1 ];
        $this->lastExtension = (strlen($temp) < 6) ? $temp : 'nx';
        curl_setopt($ch, CURLOPT_VERBOSE, 1);

        $headerInt = 0;
        $returnInt = 1;
        $binaryInt = 0;
        if($this->isOnlyHeader){
            $headerInt = 1;
            $returnInt = 0;
        }

        $result = parse_url($this->url);

        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);

        curl_setopt($ch, CURLOPT_VERBOSE, FALSE);
        curl_setopt($ch, CURLOPT_HEADER, $headerInt);

        curl_setopt($ch, CURLOPT_REFERER, $result['scheme'].'://'.$result['host']);
        curl_setopt($ch, CURLOPT_USERAGENT, $this->header);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->connectionTimeOut);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);

        if($this->isBinary) {
            $binaryInt = 1;
            $fp_tmp = &$this->file;
            curl_setopt($ch, CURLOPT_FILE, $this->file);
            curl_setopt($ch, CURLOPT_WRITEFUNCTION, function($ch, $str) use (&$fp_tmp) {
                $length = fwrite($fp_tmp, $str);
                return $length;
            });
        }

        curl_exec($ch);

        if(($this->error = curl_errno($ch))) {
            curl_close ($ch);
            return ($this->status = FALSE);
        }
        $last_url = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
        curl_close ($ch);

        if(stripos($last_url, 'http://i.imgur.com/removed.png') !== FALSE)
            return ($this->status = FALSE);

        return ($this->status = TRUE);
    }

    private $useProxy = FALSE;
    function useProxy():CurlObj {
        $this->useProxy = TRUE;

        return $this;
    }
}