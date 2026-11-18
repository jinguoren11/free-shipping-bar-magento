<?php
/**
 * Copyright (c) GetDropFlow. Licensed under the MIT License.
 */
declare(strict_types=1);

namespace GetDropFlow\FreeShippingBar\Model\Upgrade;

/**
 * Decides, before anything is touched, whether a one-click upgrade can run on this server. Every "no" has a plain
 * reason code that the settings page turns into a sentence, and the manual upgrade steps stay available.
 */
class Preflight
{
    public const MIN_FREE_BYTES = 200 * 1024 * 1024;

    /** @var ReleaseManifestParser */
    private $parser;

    /**
     * @param ReleaseManifestParser $parser
     */
    public function __construct(ReleaseManifestParser $parser)
    {
        $this->parser = $parser;
    }

    /**
     * Returns the reasons why the upgrade cannot run (empty list = it can).
     *
     * Facts: in_app_code (bool), module_writable (bool), parent_writable (bool), can_run_commands (bool),
     * php_found (bool, optional), free_bytes (int),
     * upgrade_running (bool), current_version (string), key_present (bool), has_zip (bool),
     * production_mode (bool, informative only).
     *
     * @param array $facts
     * @param ReleaseManifest $manifest
     * @return string[] reason codes
     */
    public function check(array $facts, ReleaseManifest $manifest): array
    {
        $reasons = [];
        $rules = [
            'NO_PUBLIC_KEY' => empty($facts['key_present']),
            'NOT_IN_APP_CODE' => empty($facts['in_app_code']),
            'NOT_WRITABLE' => empty($facts['module_writable']) || empty($facts['parent_writable']),
            'NO_ZIP' => empty($facts['has_zip']),
            'CANNOT_RUN_COMMANDS' => empty($facts['can_run_commands']),
            'PHP_NOT_FOUND' => array_key_exists('php_found', $facts) && empty($facts['php_found']),
            'LOW_DISK' => (int) ($facts['free_bytes'] ?? 0) < self::MIN_FREE_BYTES,
            'ALREADY_RUNNING' => !empty($facts['upgrade_running']),
            'NOT_NEWER' => !$this->parser->isNewer($manifest->version, (string) ($facts['current_version'] ?? '')),
            'TOO_OLD' => $manifest->minFrom !== null
                && $this->parser->isNewer($manifest->minFrom, (string) ($facts['current_version'] ?? '')),
        ];
        foreach ($rules as $code => $failed) {
            if ($failed) {
                $reasons[] = $code;
            }
        }
        return $reasons;
    }
}
