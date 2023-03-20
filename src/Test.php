<?php
namespace Middleware\PhpApmTest;

class Test {
    public static function printString($str): void {
        echo $str;
    }

    public static function printServerDetails($serverJson, $key = ''): void {
        if ($key != '' && isset($serverJson[$key]) && !empty($serverJson[$key])) {
            echo '<pre>';
            print_r($serverJson[$key]);
            echo '</pre>';
        } else {
            echo '<pre>';
            print_r($serverJson);
            echo '</pre>';
        }
    }

}