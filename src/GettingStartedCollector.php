<?php

declare(strict_types=1);
require __DIR__ . '/../vendor/autoload.php';

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\HttpFactory;
use OpenTelemetry\Contrib\Otlp\OtlpHttpTransportFactory;
use OpenTelemetry\Contrib\Otlp\SpanExporter;
use OpenTelemetry\SDK\Trace\SpanExporter\ConsoleSpanExporter;
use OpenTelemetry\SDK\Trace\SpanProcessor\SimpleSpanProcessor;
use OpenTelemetry\SDK\Trace\TracerProvider;

echo 'Starting OTLP/http Exporter' . PHP_EOL;

//$transport = (new OtlpHttpTransportFactory())->create('http://collector:4318/v1/traces', 'application/x-protobuf');
$transport = (new OtlpHttpTransportFactory())->create('http://localhost:9321/v1/traces', 'application/x-protobuf');
$exporter = new SpanExporter($transport);

$tracerProvider = new TracerProvider(
    new SimpleSpanProcessor(
        $exporter
    )
);

$tracer = $tracerProvider->getTracer('io.opentelemetry.contrib.php');

$rootSpan = $tracer->spanBuilder('root')->startSpan();
$rootScope = $rootSpan->activate();

try {
    $span1 = $tracer->spanBuilder('foo')->startSpan();
    $scope = $span1->activate();
    try {
        $span2 = $tracer->spanBuilder('bar')->startSpan();
        echo 'OpenTelemetry welcomes PHP' . PHP_EOL;
    } finally {
        $span2->end();
    }
} finally {
    $span1->end();
    $scope->detach();
}
$rootSpan->end();
$rootScope->detach();