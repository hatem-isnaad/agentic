<?php

namespace Agentic\Tool\Drivers\Http;

use InvalidArgumentException;

final class HttpUrlValidator
{
    public function validate(string $url): void
    {
        $parts = parse_url($url);
        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        $host = (string) ($parts['host'] ?? '');

        if (! in_array($scheme, ['http', 'https'], true) || $host === '') {
            throw new InvalidArgumentException('HTTP tool URL must use an absolute http or https URL.');
        }

        if (config('agentic.http.allow_private_hosts', false)) {
            return;
        }

        $ips = gethostbynamel($host) ?: [];

        foreach ($ips as $ip) {
            if (filter_var(
                $ip,
                FILTER_VALIDATE_IP,
                FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
            ) === false) {
                throw new InvalidArgumentException("HTTP tool host [{$host}] resolves to a private or reserved address.");
            }
        }

        if (in_array(strtolower($host), ['localhost', 'localhost.localdomain'], true)
            || str_ends_with(strtolower($host), '.localhost')
            || str_ends_with(strtolower($host), '.local')) {
            throw new InvalidArgumentException("HTTP tool host [{$host}] is not allowed.");
        }
    }
}
