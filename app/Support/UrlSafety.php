<?php

namespace App\Support;

class UrlSafety
{
    /**
     * @throws \RuntimeException
     */
    public static function assertPublicHttpUrl(string $url): void
    {
        $parts = parse_url($url);

        if (! in_array(strtolower($parts['scheme'] ?? ''), ['http', 'https'], true)) {
            throw new \RuntimeException('URL harus menggunakan skema http atau https.');
        }

        $host = strtolower($parts['host'] ?? '');

        if ($host === '' || $host === 'localhost' || str_ends_with($host, '.local')) {
            throw new \RuntimeException('URL tidak diizinkan: host lokal.');
        }

        $ips = [];

        if (filter_var($host, FILTER_VALIDATE_IP)) {
            $ips[] = $host;
        } else {
            $resolved = gethostbynamel($host) ?: [];
            $ips = array_merge($ips, $resolved);
        }

        foreach ($ips as $ip) {
            if (self::isPrivateOrReservedIp($ip)) {
                throw new \RuntimeException('URL tidak diizinkan: alamat jaringan privat.');
            }
        }
    }

    private static function isPrivateOrReservedIp(string $ip): bool
    {
        return filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        ) === false;
    }
}
