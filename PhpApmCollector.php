<?php
declare(strict_types=1);
namespace Middleware\PhpApmTest;

require 'vendor/autoload.php';

use OpenTelemetry\Context\Context;
use OpenTelemetry\API\Trace\StatusCode;
use OpenTelemetry\Contrib\Otlp\OtlpHttpTransportFactory;
use OpenTelemetry\Contrib\Otlp\SpanExporter;
use OpenTelemetry\SDK\Common\Attribute\Attributes;
use OpenTelemetry\SDK\Common\Configuration\Variables;
use OpenTelemetry\SDK\Resource\ResourceInfo;
use OpenTelemetry\Sdk\Trace\Span;
use OpenTelemetry\SDK\Trace\SpanProcessor\SimpleSpanProcessor;
use OpenTelemetry\SDK\Trace\TracerProvider;
use OpenTelemetry\API\Trace\TracerInterface;
use OpenTelemetry\SemConv\ResourceAttributes;

final class PhpApmCollector {

    private int $exportPort = 9321;
    private string $projectName;
    private string $serviceName;
    private TracerInterface $tracer;

    public function __construct(string $projectName, string $serviceName) {

        if (empty($projectName)) {
            $projectName = 'Project-'. getmypid();
        }

        if (empty($serviceName)) {
            $serviceName = 'Service-'. getmypid();
        }

        $this->projectName = $projectName;
        $this->serviceName = $serviceName;

        $transport = (new OtlpHttpTransportFactory())->create(
                'http://localhost:' . $this->exportPort . '/v1/traces',
                'application/x-protobuf');

        $exporter = new SpanExporter($transport);

        $tracerProvider = new TracerProvider(
            new SimpleSpanProcessor($exporter),
            null,
            ResourceInfo::create(Attributes::create([
                'project.name' => $projectName,
                ResourceAttributes::SERVICE_NAME => $serviceName,
                Variables::OTEL_PHP_AUTOLOAD_ENABLED => true,
            ]))
        );

        $this->tracer = $tracerProvider->getTracer('io.opentelemetry.contrib.php', '0.1.0');
    }

    public function preTracingCall(
        ?string $className,
        string $functionName,
        ?string $fileName,
        ?int $lineNo): void {
        $span = $this->tracer->spanBuilder(sprintf('%s::%s', $className, $functionName))
            ->setAttribute('service.name', $this->serviceName)
            ->setAttribute('project.name', $this->projectName)
            ->setAttribute('code.function', $functionName)
            ->setAttribute('code.namespace', $className)
            ->setAttribute('code.filepath', $fileName)
            ->setAttribute('code.lineno', $lineNo)
            ->startSpan();
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
        ?string $className,
        string $functionName,
        ?string $fileName,
        ?int $lineNo): void {
        $this->preTracingCall($className, $functionName, $fileName, $lineNo);
        $this->postTracingCall();
    }
}