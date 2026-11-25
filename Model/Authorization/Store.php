<?php
/**
 * Copyright (c) GetDropFlow. Licensed under the MIT License.
 */
declare(strict_types=1);

namespace GetDropFlow\FreeShippingBar\Model\Authorization;

/**
 * Where the store keeps its own credential (a site token, or a typed key), the last answer of the website, and a
 * waiting "log in and authorize" attempt.
 */
// phpcs:ignore Magento2.NamingConvention.InterfaceName -- short name kept, same style as HttpClient
interface Store
{
    /**
     * The saved credential, or ''.
     *
     * @return string
     */
    public function credential(): string;

    /**
     * Saves the credential (encrypted by the implementation).
     *
     * @param string $credential
     * @return void
     */
    public function saveCredential(string $credential): void;

    /**
     * Forgets credential, last answer and waiting attempt.
     *
     * @return void
     */
    public function forget(): void;

    /**
     * The last answer record, or [].
     *
     * @return array
     */
    public function record(): array;

    /**
     * Saves the answer record.
     *
     * @param array $record
     * @return void
     */
    public function saveRecord(array $record): void;

    /**
     * The waiting attempt, or null.
     *
     * @return array|null
     */
    public function pending(): ?array;

    /**
     * Saves the waiting attempt (null removes it).
     *
     * @param array|null $pending
     * @return void
     */
    public function savePending(?array $pending): void;
}
