<?php
//https://opentelemetry.io/docs/instrumentation/php/automatic/

//https://github.com/open-telemetry/opentelemetry-php-instrumentation

//https://morioh.com/p/d1f7b1aed614

declare(strict_types=1);
require __DIR__ . '/../vendor/autoload.php';


use GuzzleHttp\Client;
use GuzzleHttp\Psr7\HttpFactory;
use OpenTelemetry\Contrib\Otlp\OtlpHttpTransportFactory;
use OpenTelemetry\Contrib\Otlp\SpanExporter;
use OpenTelemetry\SDK\Trace\SpanProcessor\SimpleSpanProcessor;

use OpenTelemetry\SDK\Trace\TracerProvider;
use OpenTelemetry\SDK\Common\Instrumentation As InstrumentationLibrary;

//putenv('OTEL_EXPORTER_OTLP_ENDPOINT=http://localhost:9321/v1/traces');
//putenv('OTEL_EXPORTER_OTLP_PROTOCOL=http/protobuf');
putenv('OTEL_PHP_AUTOLOAD_ENABLED=true');
putenv('OTEL_SERVICE_NAME=mw-php-app');

echo 'Starting CollectorSpanExporter' . PHP_EOL;

$transport = (new OtlpHttpTransportFactory())->create('http://localhost:9321/v1/traces', 'application/x-protobuf');
$exporter = new SpanExporter($transport);

$tracerProvider = new TracerProvider(
    new SimpleSpanProcessor(
        $exporter
    )
);

$tracer = $tracerProvider->getTracer('io.opentelemetry.contrib.php');
InstrumentationLibrary\hook(
    DemoClass::class,
    'run',
    static function (DemoClass $demo, array $params, string $class, string $function, ?string $filename, ?int $lineno) use ($tracer) {
        /*static $instrumentation;
        $instrumentation ??= new CachedInstrumentation('example');
        $instrumentation->tracer()->spanBuilder($class)
            ->startSpan()
            ->activate();*/

        $span = $tracer->spanBuilder($class)
            ->startSpan()
            ->activate();
        Context::storage()->attach($span->storeInContext(Context::getCurrent()));
    },
    static function (DemoClass $demo, array $params, $returnValue, ?Throwable $exception) use ($tracer) {
        $scope = Context::storage()->scope();
        $scope->detach();
        $span = Span::fromContext($scope->context());
        if ($exception) {
            $span->recordException($exception);
            $span->setStatus(StatusCode::STATUS_ERROR);
        }
        $span->end();
    }
);

$demo = new DemoClass();
$demo->run();