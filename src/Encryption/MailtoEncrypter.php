<?php

declare(strict_types=1);

namespace tdoescher\CryptMailtoBundle\Encryption;

/**
 * Verschlüsselt mailto-Adressen deterministisch mit libsodium (XSalsa20-Poly1305).
 * Der Schlüssel wird aus dem kernel.secret abgeleitet.
 */
class MailtoEncrypter
{
    private readonly string $key;

    public function __construct(#[\SensitiveParameter] string $secret)
    {
        $this->key = sodium_crypto_generichash('tdoescher/cryptmailto-bundle'.$secret, '', SODIUM_CRYPTO_SECRETBOX_KEYBYTES);
    }

    public function encrypt(string $value): string
    {
        // Deterministische Nonce: identische Adressen ergeben identische URLs (cachefreundlich)
        $nonce = sodium_crypto_generichash($value, $this->key, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);

        return rtrim(strtr(base64_encode($nonce.sodium_crypto_secretbox($value, $nonce, $this->key)), '+/', '-_'), '=');
    }

    public function decrypt(string $value): string|null
    {
        $data = base64_decode(strtr($value, '-_', '+/'), true);

        if (false === $data || \strlen($data) <= SODIUM_CRYPTO_SECRETBOX_NONCEBYTES + SODIUM_CRYPTO_SECRETBOX_MACBYTES) {
            return null;
        }

        $nonce = substr($data, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $plain = sodium_crypto_secretbox_open(substr($data, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES), $nonce, $this->key);

        return false === $plain ? null : $plain;
    }
}
