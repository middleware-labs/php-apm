<?php
declare(strict_types=1);

require 'vendor/autoload.php';
use Middleware\PhpApmTest\PhpApmCollector;


class Test {
    public static function printString($str): void {
        echo $str . PHP_EOL;
    }
}

//Test::printString('Hello..called. from Outside.');

class DemoClass {
    public static function runCode(): void {
        $mwCollector = new PhpApmCollector('test-project-51', 'test-service-51');
        $mwCollector->tracingCall(get_called_class(), __FUNCTION__, __FILE__, __LINE__);
        // $mwCollector->preTracingCall(get_called_class(), __FUNCTION__, __FILE__, __LINE__);

        Test::printString('Hello..called. from DemoClass' . PHP_EOL);
        // $mwCollector->postTracingCall();

    }
}

class TestClass {
    public static function printString(): void {
        $mwCollector = new PhpApmCollector('test-project-52', 'test-service-52');
        $mwCollector->tracingCall(get_called_class(), __FUNCTION__, __FILE__, __LINE__);

        Test::printString('Hello..called. from TestClass' . PHP_EOL);

    }
}

DemoClass::runCode();
TestClass::printString();