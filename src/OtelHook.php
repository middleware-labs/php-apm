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
use OpenTelemetry\SDK\Trace\SpanExporter\ConsoleSpanExporter;
use OpenTelemetry\SDK\Trace\SpanProcessor\SimpleSpanProcessor;
use OpenTelemetry\SDK\Trace\TracerProvider;

\OpenTelemetry\Instrumentation\hook(
    DemoClass::class,
    'run',
    static function (DemoClass $demo, array $params, string $class, string $function, ?string $filename, ?int $lineno) use ($tracer) {
        static $instrumentation;
        $instrumentation ??= new CachedInstrumentation('example');
        $instrumentation->tracer()->spanBuilder($class)
            ->startSpan()
            ->activate();
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