<?php
declare(strict_types=1);
namespace Middleware\PhpApmTest;

require 'vendor/autoload.php';
//require __DIR__ . '/../vendor/autoload.php';

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\HttpFactory;
use OpenTelemetry\Sdk\Trace\Span;
use OpenTelemetry\Context\Context;
use OpenTelemetry\Context\ContextValue;
use OpenTelemetry\API\Trace\StatusCode;

use OpenTelemetry\Contrib\Otlp\OtlpHttpTransportFactory;
use OpenTelemetry\Contrib\Otlp\SpanExporter;
use OpenTelemetry\SDK\Trace\SpanProcessor\SimpleSpanProcessor;

//use function OpenTelemetry\Instrumentation\hook;
use OpenTelemetry\SDK\Trace\TracerProvider;
use OpenTelemetry\SDK\Common\Instrumentation As Instrumentation;
use Psr\Http\Message\ResponseInterface;
use OpenTelemetry\SDK\Resource\Attributes;

//use function OpenTelemetry\Instrumentation\hook;

//putenv('OTEL_EXPORTER_OTLP_ENDPOINT=http://localhost:9321/v1/traces');
//putenv('OTEL_EXPORTER_OTLP_PROTOCOL=http/protobuf');
putenv('OTEL_PHP_AUTOLOAD_ENABLED=true');
//putenv('OTEL_SERVICE_NAME=mw-php-app');
//$service = getenv('MW_PHP_SERVICE_NAME');
//putenv('OTEL_SERVICE_NAME=' . $service);

//echo 'Hello..called.';
$transport = (new OtlpHttpTransportFactory())
    ->create('http://localhost:9321/v1/traces', 'application/x-protobuf');
$exporter = new SpanExporter($transport);

$tracerProvider = new TracerProvider(
    new SimpleSpanProcessor($exporter),
    new Attributes(['service.name' => 'mw-php-app123'])
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

    public static function callOtelCodeBefore(?string $classname, string $functionname, ?string $filename, ?int $lineno): void {
        global $tracer;
        // $span = $tracer->spanBuilder('DemoClass')->startSpan();
        $span = $tracer->spanBuilder(sprintf('%s::%s', $classname, $functionname))
            ->setAttribute('function', $functionname)
            ->setAttribute('code.namespace', $classname)
            ->setAttribute('code.filepath', $filename)
            ->setAttribute('code.lineno', $lineno)->startSpan();
        Context::storage()->attach($span->storeInContext(Context::getCurrent()));
    }

    public static function callOtelCodeAfter(): void {
        $scope = Context::storage()->scope();
        $scope?->detach();
        $span = Span::fromContext($scope->context());
        $span->setStatus(StatusCode::STATUS_OK);
        $span->end();
    }

    public static function callOtelCodeCombined(?string $classname, string $functionname, ?string $filename, ?int $lineno): void {
        global $tracer;
        $span = $tracer->spanBuilder(sprintf('%s::%s', $classname, $functionname))
            ->setAttribute('function', $functionname)
            ->setAttribute('code.namespace', $classname)
            ->setAttribute('code.filepath', $filename)
            ->setAttribute('code.lineno', $lineno)->startSpan();
        $scope = $span->activate();

        $scope?->detach();
        $span = Span::fromContext($scope->context());
        $span->setStatus(StatusCode::STATUS_OK);
        $span->end();
    }

}

//Test::printString('Hello..called. from DemoClass');
