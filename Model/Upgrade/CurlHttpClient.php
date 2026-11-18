<?php
/**
 * Copyright (c) GetDropFlow. Licensed under the MIT License.
 */
declare(strict_types=1);

// phpcs:disable Magento2.Functions.DiscouragedFunction -- Download edition only: streams the package to disk with a
// size cap while it downloads (fopen/fwrite/fclose); Magento's file driver cannot be used inside a curl callback.
// phpcs:disable Magento2.Exceptions.TryProcessSystemResources -- the handle is closed in finally on purpose

namespace GetDropFlow\FreeShippingBar\Model\Upgrade;

/**
 * GET requests with curl. Only https://github.com addresses (and the GitHub download hosts GitHub redirects to)
 * are accepted; the size is capped while downloading.
 */
class CurlHttpClient implements HttpClient
{
    private const ALLOWED_START = [
        'https://api.github.com/repos/' . ReleaseManifest::REPO . '/',
        'https://github.com/' . ReleaseManifest::REPO . '/releases/',
    ];

    /** @var string local test server address (http://127.0.0.1:PORT/), or '' */
    private $testBase;

    /**
     * @param string $testBase tests only: extra allowed address on this machine, e.g. http://127.0.0.1:8099/
     */
    public function __construct(string $testBase = '')
    {
        $this->testBase = preg_match('#^http://127\.0\.0\.1:\d{2,5}/$#', $testBase) === 1 ? $testBase : '';
    }

    /**
     * @inheritDoc
     */
    public function getText(string $url, int $maxBytes): string
    {
        $buffer = '';
        $this->request($url, $maxBytes, function (string $chunk) use (&$buffer): void {
            $buffer .= $chunk;
        });
        return $buffer;
    }

    /**
     * @inheritDoc
     */
    public function download(string $url, string $destination, int $maxBytes): void
    {
        $handle = fopen($destination, 'wb');
        if ($handle === false) {
            throw new UpgradeRejected('DOWNLOAD_FAILED', 'The download folder is not writable.');
        }
        try {
            $this->request($url, $maxBytes, function (string $chunk) use ($handle): void {
                fwrite($handle, $chunk);
            });
        } finally {
            fclose($handle);
        }
    }

    /**
     * Runs the request and hands the body to $sink in chunks.
     *
     * @param string $url
     * @param int $maxBytes
     * @param callable $sink
     * @return void
     * @throws UpgradeRejected
     */
    private function request(string $url, int $maxBytes, callable $sink): void
    {
        $allowed = false;
        foreach (self::ALLOWED_START as $start) {
            $allowed = $allowed || (strpos($url, $start) === 0 && strpos($url, '..') === false);
        }
        $local = $this->testBase !== '' && strpos($url, $this->testBase) === 0 && strpos($url, '..') === false;
        $allowed = $allowed || $local;
        if (!$allowed || !function_exists('curl_init')) {
            throw new UpgradeRejected('BAD_URL', 'The address is not allowed.');
        }
        $received = 0;
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 5,
            CURLOPT_PROTOCOLS => $local ? CURLPROTO_HTTP : CURLPROTO_HTTPS,
            CURLOPT_REDIR_PROTOCOLS => $local ? CURLPROTO_HTTP : CURLPROTO_HTTPS,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 120,
            CURLOPT_USERAGENT => 'GetDropFlow-FreeShippingBar-Upgrade',
            CURLOPT_HTTPHEADER => ['Accept: application/vnd.github+json, application/octet-stream'],
            CURLOPT_WRITEFUNCTION => function ($handle, string $chunk) use (&$received, $maxBytes, $sink): int {
                $received += strlen($chunk);
                if ($received > $maxBytes) {
                    return 0; // aborts the transfer
                }
                $sink($chunk);
                return strlen($chunk);
            },
        ]);
        $ok = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);
        if ($ok === false || $code !== 200) {
            throw new UpgradeRejected('DOWNLOAD_FAILED', 'The download did not work.');
        }
    }
}
