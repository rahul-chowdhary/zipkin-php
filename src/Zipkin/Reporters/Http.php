<?php

namespace Zipkin\Reporters;

use RuntimeException;
use Zipkin\Recording\Span;
use Zipkin\Reporter;
use Zipkin\Reporters\Http\ClientFactory;
use Zipkin\Reporters\Http\CurlFactory;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

final class Http implements Reporter
{
    const DEFAULT_OPTIONS = [
        'endpoint_url' => 'http://localhost:9411/api/v2/spans',
    ];

    /**
     * @var ClientFactory
     */
    private $clientFactory;

    /**
     * @var array
     */
    private $options;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        ClientFactory $requesterFactory = null,
        array $options = [],
        ?LoggerInterface $logger = null
    ) {
        $this->clientFactory = $requesterFactory ?: CurlFactory::create();
        $this->options = array_merge(self::DEFAULT_OPTIONS, $options);
        $this->logger = $logger ?? new NullLogger();
    }

    /**
     * @param Span[] $spans
     * @return void
     */
    public function report(array $spans)
    {
        $payload = json_encode(array_map(function (Span $span) {
            return $span->toArray();
        }, $spans));
        if ($payload === false) {
            $this->logger->error(
                sprintf('failed to encode spans with code %d', \json_last_error())
            );
            return;
        }

        $client = $this->clientFactory->build($this->options);
        try {
            $client($payload);
        } catch (RuntimeException $e) {
            $this->logger->error($e->getMessage());
        }
    }
}
