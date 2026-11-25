<?php
/**
 * Copyright (c) GetDropFlow. Licensed under the MIT License.
 */
declare(strict_types=1);

// phpcs:disable Magento2.Functions.DiscouragedFunction -- Download edition only: a small curl call with strict limits

namespace GetDropFlow\FreeShippingBar\Model\Authorization;

/**
 * curl, https only, only to the license server, short timeouts, no redirects, small answers only.
 * Nothing but the store's host name (and, when the merchant presses the button, the credential) is sent.
 */
class CurlTransport implements Transport
{
    private const HOST = 'license.getdropflow.com';
    private const MAX_BYTES = 65536;

    /**
     * @inheritDoc
     */
    public function send(string $method, string $url, array $headers = [], ?string $body = null): array
    {
        if (strpos($url, 'https://' . self::HOST . '/') !== 0 || strpos($url, '..') !== false || !function_exists('curl_init')) {
            throw new \RuntimeException('not allowed');
        }
        $lines = [];
        foreach ($headers as $name => $value) {
            $lines[] = $name . ': ' . $value;
        }
        $received = '';
        $responseHeaders = [];
        $ch = curl_init($url);
        $options = [
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $lines,
            CURLOPT_CONNECTTIMEOUT => 3,
            CURLOPT_TIMEOUT => 8,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_PROTOCOLS => CURLPROTO_HTTPS,
            CURLOPT_USERAGENT => 'GetDropFlow-FreeShippingBar',
            CURLOPT_HEADERFUNCTION => function ($handle, string $line) use (&$responseHeaders): int {
                $pos = strpos($line, ':');
                if ($pos !== false) {
                    $responseHeaders[strtolower(trim(substr($line, 0, $pos)))] = trim(substr($line, $pos + 1));
                }
                return strlen($line);
            },
            CURLOPT_WRITEFUNCTION => function ($handle, string $chunk) use (&$received): int {
                $received .= $chunk;
                return strlen($received) > self::MAX_BYTES ? 0 : strlen($chunk);
            },
        ];
        if ($body !== null) {
            $options[CURLOPT_POSTFIELDS] = $body;
        }
        curl_setopt_array($ch, $options);
        $ok = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);
        if ($ok === false || $status === 0) {
            throw new \RuntimeException('no answer');
        }
        return ['status' => $status, 'headers' => $responseHeaders, 'body' => $received];
    }
}
