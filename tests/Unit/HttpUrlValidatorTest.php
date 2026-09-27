<?php

namespace Agentic\Tests\Unit;

use Agentic\Tool\Drivers\Http\HttpUrlValidator;
use Agentic\Tests\TestCase;
use InvalidArgumentException;

final class HttpUrlValidatorTest extends TestCase
{
    public function test_private_hosts_are_rejected_by_default(): void
    {
        config(['agentic.http.allow_private_hosts' => false]);

        $this->expectException(InvalidArgumentException::class);

        (new HttpUrlValidator())->validate('http://127.0.0.1/internal');
    }

    public function test_non_http_urls_are_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new HttpUrlValidator())->validate('file:///etc/passwd');
    }

    public function test_private_hosts_can_be_explicitly_allowed_by_host_application(): void
    {
        config(['agentic.http.allow_private_hosts' => true]);

        (new HttpUrlValidator())->validate('http://127.0.0.1/internal');

        $this->assertTrue(true);
    }

    public function test_urls_with_embedded_credentials_are_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new HttpUrlValidator())->validate('https://user:pass@api.example.test/resource');
    }

    public function test_unresolved_hosts_are_rejected_by_default(): void
    {
        config([
            'agentic.http.allow_private_hosts' => false,
            'agentic.http.allow_unresolved_hosts' => false,
        ]);

        $this->expectException(InvalidArgumentException::class);

        (new HttpUrlValidator())->validate('https://api.example.test/resource');
    }
}
