<?php
declare(strict_types=1);
namespace Middleware\PhpApmTest;

//"open-telemetry/sdk": "^1.0.0beta3",
//"open-telemetry/api": "1.0.0beta4",

//https://opentelemetry.io/docs/instrumentation/php/automatic/

//https://github.com/open-telemetry/opentelemetry-php-instrumentation

//https://morioh.com/p/d1f7b1aed614

require __DIR__ . '/../vendor/autoload.php';

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\HttpFactory;
use OpenTelemetry\Contrib\Otlp\OtlpHttpTransportFactory;
use OpenTelemetry\Contrib\Otlp\SpanExporter;
use OpenTelemetry\SDK\Trace\SpanProcessor\SimpleSpanProcessor;

use OpenTelemetry\SDK\Trace\TracerProvider;
use OpenTelemetry\SDK\Common\Instrumentation As Instrumentation;

//putenv('OTEL_EXPORTER_OTLP_ENDPOINT=http://localhost:9321/v1/traces');
//putenv('OTEL_EXPORTER_OTLP_PROTOCOL=http/protobuf');
putenv('OTEL_PHP_AUTOLOAD_ENABLED=true');
putenv('OTEL_SERVICE_NAME=mw-php-app');

echo 'Starting CollectorSpanExporter' . PHP_EOL;

class DemoClass {
    public static function run() {
        echo 'Hello..called.';
        $transport = (new OtlpHttpTransportFactory())->create('http://localhost:9321/v1/traces', 'application/x-protobuf');
        $exporter = new SpanExporter($transport);

        $tracerProvider = new TracerProvider(
            new SimpleSpanProcessor(
                $exporter
            )
        );

        //$tracer = $tracerProvider->getTracer('io.opentelemetry.contrib.php');
        $tracer = new Tracer($tracerProvider);

        Instrumentation\hook(
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
    }
}
