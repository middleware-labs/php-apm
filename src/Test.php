<?php
namespace Middleware\PhpApmTest;

class Test {
    public static function printString($str): void {
        echo $str;
    }

    public static function printServerDetails($serverJson): void {
        echo '<pre>';
        print_r($serverJson);
        echo '</pre>';
    }

}