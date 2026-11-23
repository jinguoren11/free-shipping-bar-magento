<?php
/**
 * Copyright (c) GetDropFlow. Licensed under the MIT License.
 */
declare(strict_types=1);

namespace GetDropFlow\FreeShippingBar\Model\Authorization;

use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\FlagManager;

/**
 * Keeps the data in Magento's own "flag" table (not in the configuration, so saving it never clears the configuration cache).
 * The credential and the waiting attempt are encrypted with the shop's key.
 */
class FlagStore implements Store
{
    private const CREDENTIAL = 'getdropflow_fsb_credential';
    private const RECORD = 'getdropflow_fsb_answer';
    private const PENDING = 'getdropflow_fsb_pending';

    /**
     * @var FlagManager
     */
    private $flags;

    /**
     * @var EncryptorInterface
     */
    private $encryptor;

    /**
     * @param FlagManager $flags
     * @param EncryptorInterface $encryptor
     */
    public function __construct(FlagManager $flags, EncryptorInterface $encryptor)
    {
        $this->flags = $flags;
        $this->encryptor = $encryptor;
    }

    /**
     * @inheritDoc
     */
    public function credential(): string
    {
        $raw = $this->flags->getFlagData(self::CREDENTIAL);
        return is_string($raw) && $raw !== '' ? trim((string) $this->encryptor->decrypt($raw)) : '';
    }

    /**
     * @inheritDoc
     */
    public function saveCredential(string $credential): void
    {
        $this->flags->saveFlag(self::CREDENTIAL, $this->encryptor->encrypt($credential));
    }

    /**
     * @inheritDoc
     */
    public function forget(): void
    {
        $this->flags->deleteFlag(self::CREDENTIAL);
        $this->flags->deleteFlag(self::RECORD);
        $this->flags->deleteFlag(self::PENDING);
    }

    /**
     * @inheritDoc
     */
    public function record(): array
    {
        $data = $this->flags->getFlagData(self::RECORD);
        return is_array($data) ? $data : [];
    }

    /**
     * @inheritDoc
     */
    public function saveRecord(array $record): void
    {
        $this->flags->saveFlag(self::RECORD, $record);
    }

    /**
     * @inheritDoc
     */
    public function pending(): ?array
    {
        $raw = $this->flags->getFlagData(self::PENDING);
        if (!is_string($raw) || $raw === '') {
            return null;
        }
        $data = json_decode((string) $this->encryptor->decrypt($raw), true);
        return is_array($data) ? $data : null;
    }

    /**
     * @inheritDoc
     */
    public function savePending(?array $pending): void
    {
        if ($pending === null) {
            $this->flags->deleteFlag(self::PENDING);
            return;
        }
        $this->flags->saveFlag(self::PENDING, $this->encryptor->encrypt((string) json_encode($pending)));
    }
}
