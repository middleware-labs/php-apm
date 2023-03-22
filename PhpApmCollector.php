<?php
declare(strict_types=1);
namespace Middleware\PhpApmTest;

require 'vendor/autoload.php';

use OpenTelemetry\Context\Context;
use OpenTelemetry\API\Trace\StatusCode;
use OpenTelemetry\Contrib\Otlp\OtlpHttpTransportFactory;
use OpenTelemetry\Contrib\Otlp\SpanExporter;
use OpenTelemetry\Sdk\Trace\Span;
use OpenTelemetry\SDK\Trace\SpanProcessor\SimpleSpanProcessor;
use OpenTelemetry\SDK\Trace\TracerProvider;
//use OpenTelemetry\SDK\Trace\SpanKind;
use OpenTelemetry\API\Trace\TracerInterface;

//putenv('OTEL_EXPORTER_OTLP_ENDPOINT=http://localhost:9321/v1/traces');
//putenv('OTEL_EXPORTER_OTLP_PROTOCOL=http/protobuf');
putenv('OTEL_PHP_AUTOLOAD_ENABLED=true');

final class PhpApmCollector {

    private int $exportPort = 9321;
    private string $serviceName;
    private TracerInterface $tracer;

    public function __construct(string $serviceName) {

        assert(!isset($requiredArg), 'Service name is required.');

        $this->serviceName = $serviceName;

        $transport = (new OtlpHttpTransportFactory())->create(
                'http://localhost:' . $this->exportPort . '/v1/traces',
                'application/x-protobuf');

        $exporter = new SpanExporter($transport);

        $tracerProvider = new TracerProvider(
            new SimpleSpanProcessor($exporter),
        );

        $this->tracer = $tracerProvider->getTracer('io.opentelemetry.contrib.php');
    }

    public function preTracingCall(
        ?string $classname,
        string $functionname,
        ?string $filename,
        ?int $lineno): void {
        $span = $this->tracer->spanBuilder(sprintf('%s::%s', $classname, $functionname))
            ->setAttribute('service.name', $this->serviceName)
            ->setAttribute('function', $functionname)
            ->setAttribute('code.namespace', $classname)
            ->setAttribute('code.filepath', $filename)
            ->setAttribute('code.lineno', $lineno)->startSpan();
        // $span = $tracer
        //     ->spanBuilder(sprintf('%s %s', $request->getMethod(), $request->getUri()))
        //     ->setSpanKind(SpanKind::KIND_CLIENT)
        //     ->setAttribute('http.method', $request->getMethod())
        //     ->setAttribute('http.url', $request->getUri())
        //     ->startSpan();
        // $span = $tracer
        //     ->spanBuilder('get-user')
        //     ->setAttribute('db.system', 'mysql')
        //     ->setAttribute('db.name', 'users')
        //     ->setAttribute('db.user', 'some_user')
        //     ->setAttribute('db.statement', 'select * from users where username = :1')
        //     ->startSpan();
        Context::storage()->attach($span->storeInContext(Context::getCurrent()));
    }

    public function postTracingCall(): void {
        $scope = Context::storage()->scope();
        $scope?->detach();
        $span = Span::fromContext($scope->context());
        $span->setStatus(StatusCode::STATUS_OK);
        $span->end();
    }

    public function tracingCall(
        ?string $classname,
        string $functionname,
        ?string $filename,
        ?int $lineno): void {
        $this->preTracingCall($classname, $functionname, $filename, $lineno);
        $this->postTracingCall();
    }
}