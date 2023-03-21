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
use OpenTelemetry\SemConv\TraceAttributes;
use Psr\Http\Message\ResponseInterface;

//use function OpenTelemetry\Instrumentation\hook;

//putenv('OTEL_EXPORTER_OTLP_ENDPOINT=http://localhost:9321/v1/traces');
//putenv('OTEL_EXPORTER_OTLP_PROTOCOL=http/protobuf');
putenv('OTEL_PHP_AUTOLOAD_ENABLED=true');
putenv('OTEL_SERVICE_NAME=mw-php-app');

//echo 'Hello..called.';
$transport = (new OtlpHttpTransportFactory())
    ->create('http://localhost:9321/v1/traces', 'application/x-protobuf');
$exporter = new SpanExporter($transport);

$tracerProvider = new TracerProvider(
    new SimpleSpanProcessor(
        $exporter
    )
);
$tracer = $tracerProvider->getTracer('io.opentelemetry.contrib.php');

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

    public static function callOtelCodeBefore(): void {
        global $tracer;
        // $span = $tracer->spanBuilder('DemoClass')->startSpan();
        $span = $tracer->spanBuilder(sprintf('%s::%s', 'DemoClass', 'run'))
            ->setAttribute('function', 'run()')
            ->setAttribute('code.namespace', 'DemoClass')
            ->setAttribute('code.filepath', 'TestOtel.php')
            ->setAttribute('code.lineno', '122')->startSpan();
        Context::storage()->attach($span->storeInContext(Context::getCurrent()));
    }

    public static function callOtelCodeAfter(): void {
        $scope = Context::storage()->scope();
        $scope?->detach();
        $span = Span::fromContext($scope->context());
        $span->setStatus(StatusCode::STATUS_OK);
        $span->end();
    }

}