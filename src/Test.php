<?php
declare(strict_types=1);
namespace Middleware\PhpApmTest2;

require 'vendor/autoload.php';
//require __DIR__ . '/../vendor/autoload.php';

use OpenTelemetry\Sdk\Trace\Span;
use OpenTelemetry\Context\Context;
use OpenTelemetry\API\Trace\StatusCode;

use OpenTelemetry\Contrib\Otlp\OtlpHttpTransportFactory;
use OpenTelemetry\Contrib\Otlp\SpanExporter;
use OpenTelemetry\SDK\Trace\SpanProcessor\SimpleSpanProcessor;

use OpenTelemetry\SDK\Trace\TracerProvider;


//putenv('OTEL_EXPORTER_OTLP_ENDPOINT=http://localhost:9321/v1/traces');
//putenv('OTEL_EXPORTER_OTLP_PROTOCOL=http/protobuf');
putenv('OTEL_PHP_AUTOLOAD_ENABLED=true');


$transport = (new OtlpHttpTransportFactory())
    ->create(
        'http://localhost:9321/v1/traces',
        'application/x-protobuf'
    );
$exporter = new SpanExporter($transport);

$tracerProvider = new TracerProvider(
    new SimpleSpanProcessor($exporter),
);

global $tracer;
$tracer = $tracerProvider->getTracer('io.opentelemetry.contrib.php');

class Test {
    public static function printString($str): void {
//        \Middleware\PhpApmTest\Test::callOtelCodeCombined(get_called_class(), __FUNCTION__, __FILE__, __LINE__);
//        \Middleware\PhpApmTest\Test::callOtelCodeBefore();
        echo $str;
//        \Middleware\PhpApmTest\Test::callOtelCodeAfter();
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

    public static function preTracingCall(?string $servicename, ?string $classname, string $functionname, ?string $filename, ?int $lineno): void {
        global $tracer;
        // $span = $tracer->spanBuilder('DemoClass')->startSpan();
        $span = $tracer->spanBuilder(sprintf('%s::%s', $classname, $functionname))
            ->setAttribute('service.name', $servicename)
            ->setAttribute('function', $functionname)
            ->setAttribute('code.namespace', $classname)
            ->setAttribute('code.filepath', $filename)
            ->setAttribute('code.lineno', $lineno)->startSpan();
        Context::storage()->attach($span->storeInContext(Context::getCurrent()));
    }

    public static function postTracingCall(): void {
        $scope = Context::storage()->scope();
        $scope?->detach();
        $span = Span::fromContext($scope->context());
        $span->setStatus(StatusCode::STATUS_OK);
        $span->end();
    }

    public static function tracingCall(?string $servicename, ?string $classname, string $functionname, ?string $filename, ?int $lineno): void {
        self::preTracingCall($servicename, $classname, $functionname, $filename, $lineno);
        self::postTracingCall();
    }

}

//Test::printString('Hello..called. from DemoClass');
