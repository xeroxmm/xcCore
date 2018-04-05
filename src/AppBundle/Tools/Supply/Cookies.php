<?php
namespace AppBundle\Tools\Supply;

use Symfony\Component\BrowserKit\Request;
use Symfony\Component\HttpFoundation\ParameterBag;

class Cookies {
    public static function setPopularCookie(bool $bool, ParameterBag $param){
        setcookie('x9001s', (int)$bool, time() + 720 * 60 * 60 * 24);
        $param->set('x9001s', (int)$bool);
    }
    public static function setByPopularSlug(String $slug, ParameterBag $param){
        if($slug == 'latest' || $slug == 'related')
            Cookies::setPopularCookie( FALSE , $param );
        else if($slug == 'popular')
            Cookies::setPopularCookie( TRUE , $param  );
    }
}