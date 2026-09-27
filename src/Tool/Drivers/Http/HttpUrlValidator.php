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

        if (isset($parts['user']) || isset($parts['pass'])) {
            throw new InvalidArgumentException('HTTP tool URL must not include embedded credentials.');
        }

        if (config('agentic.http.allow_private_hosts', false)) {
            return;
        }

        $this->rejectBlockedHostnames($host);

        if (filter_var($host, FILTER_VALIDATE_IP)) {
            $this->rejectNonPublicIp($host);

            return;
        }

        $ips = gethostbynamel($host) ?: [];

        if ($ips === [] && ! config('agentic.http.allow_unresolved_hosts', false)) {
            throw new InvalidArgumentException("HTTP tool host [{$host}] could not be resolved.");
        }

        foreach ($ips as $ip) {
            $this->rejectNonPublicIp($ip);
        }
    }

    private function rejectBlockedHostnames(string $host): void
    {
        $normalized = strtolower($host);

        if (in_array($normalized, ['localhost', 'localhost.localdomain'], true)
            || str_ends_with($normalized, '.localhost')
            || str_ends_with($normalized, '.local')
            || str_ends_with($normalized, '.internal')) {
            throw new InvalidArgumentException("HTTP tool host [{$host}] is not allowed.");
        }
    }

    private function rejectNonPublicIp(string $ip): void
    {
        if (filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        ) === false) {
            throw new InvalidArgumentException("HTTP tool host resolves to a private or reserved address [{$ip}].");
        }
    }
}
