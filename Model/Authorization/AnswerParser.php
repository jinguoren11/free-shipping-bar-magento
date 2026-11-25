<?php
/**
 * Copyright (c) GetDropFlow. Licensed under the MIT License.
 */
declare(strict_types=1);

namespace GetDropFlow\FreeShippingBar\Model\Authorization;

/**
 * Turns the signed 200 body of GET /v1/license/status into an Answer: Ed25519 signature over the exact body bytes,
 * JSON shape, the domain it was issued for, and the age (48 hours). Anything wrong gives null (treated as "not reachable").
 */
class AnswerParser
{
    /**
     * Parses and verifies an answer.
     *
     * @param string $body
     * @param string|null $signatureBase64
     * @param string $domain
     * @param int $now
     * @param string $publicKeyBase64
     * @return Answer|null
     */
    public function parse(string $body, ?string $signatureBase64, string $domain, int $now, string $publicKeyBase64): ?Answer
    {
        if (!$this->authentic($body, $signatureBase64, $publicKeyBase64)) {
            return null;
        }
        $d = json_decode($body, true);
        if (!is_array($d) || ($d['domain'] ?? null) !== $domain || !isset($d['valid'], $d['plan'], $d['issued_at'], $d['expires_at'])) {
            return null;
        }
        $issued = strtotime((string) $d['issued_at']);
        $expires = strtotime((string) $d['expires_at']);
        if ($issued === false || $expires === false || $now - $issued > ServerKey::MAX_AGE_SECONDS) {
            return null;
        }
        $product = array_key_exists('product', $d) ? $d['product'] : 'getdropflow';
        $accepted = in_array($product, ['getdropflow', 'social'], true);
        $lapsedPlan = null;
        $lapsedAt = null;
        if ($accepted && isset($d['lapsed']) && is_array($d['lapsed']) && is_string($d['lapsed']['plan'] ?? null)
            && is_string($d['lapsed']['ended_at'] ?? null) && strtotime($d['lapsed']['ended_at']) !== false) {
            $lapsedPlan = $d['lapsed']['plan'];
            $lapsedAt = (int) strtotime($d['lapsed']['ended_at']);
        }
        return new Answer(
            $accepted && $d['valid'] === true,
            (string) $d['plan'],
            $expires,
            $accepted ? (int) ($d['max_stores'] ?? 0) : 0,
            $lapsedPlan,
            $lapsedAt
        );
    }

    /**
     * Checks the signature.
     *
     * @param string $body
     * @param string|null $signatureBase64
     * @param string $publicKeyBase64
     * @return bool
     */
    private function authentic(string $body, ?string $signatureBase64, string $publicKeyBase64): bool
    {
        if ($signatureBase64 === null || !function_exists('sodium_crypto_sign_verify_detached')) {
            return false;
        }
        $sig = base64_decode($signatureBase64, true);
        $pk = base64_decode($publicKeyBase64, true);
        return $sig !== false && $pk !== false
            && strlen($sig) === SODIUM_CRYPTO_SIGN_BYTES
            && strlen($pk) === SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES
            && sodium_crypto_sign_verify_detached($sig, $body, $pk);
    }
}
