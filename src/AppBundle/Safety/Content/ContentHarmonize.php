<?php
namespace AppBundle\Safety\Content;

class ContentHarmonize {
    public static function getSlugOfString(string $string):string {
        $link = str_replace("&nbsp;", " ", strtolower($string));
        $link = str_replace(' ', '-', $link);

        $link = mb_convert_case($link, MB_CASE_LOWER, "UTF-8"); //convert to lowercase
        $link = preg_replace("#[^a-zA-Z0-9]+#", "-", $link); //replace everything non an with dashes
        $link = preg_replace("#(-){2,}#", "$1", $link); //replace multiple dashes with one
        $link = trim($link, "-."); //trim dashes from beginning and end of string if any

        if (strlen($link) > 100)
            $link = substr($link, 0, 95) . '-more';

        return $link;
    }
}