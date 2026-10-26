<?php

namespace App\Support;

use InvalidArgumentException;

/**
 * JWT HS256 mínimo (lo que usa OnlyOffice). Sin dependencias externas.
 */
final class Jwt
{
    public static function encode(array $payload, string $secret): string
    {
        $segments = [
            self::b64(json_encode(['alg' => 'HS256', 'typ' => 'JWT'], JSON_THROW_ON_ERROR)),
            self::b64(json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)),
        ];

        $segments[] = self::b64(hash_hmac('sha256', implode('.', $segments), $secret, true));

        return implode('.', $segments);
    }

    /**
     * @return array<string, mixed>
     *
     * @throws InvalidArgumentException si el token es inválido, manipulado o está vencido
     */
    public static function decode(string $token, string $secret): array
    {
        $parts = explode('.', $token);

        if (count($parts) !== 3) {
            throw new InvalidArgumentException('Token mal formado.');
        }

        [$header64, $payload64, $signature] = $parts;

        $header = json_decode(self::unb64($header64), true);

        // Se exige HS256: se rechaza "none" y cualquier otro algoritmo.
        if (! is_array($header) || ($header['alg'] ?? null) !== 'HS256') {
            throw new InvalidArgumentException('Algoritmo no permitido.');
        }

        $expected = self::b64(hash_hmac('sha256', $header64.'.'.$payload64, $secret, true));

        if (! hash_equals($expected, $signature)) {
            throw new InvalidArgumentException('Firma inválida.');
        }

        $payload = json_decode(self::unb64($payload64), true);

        if (! is_array($payload)) {
            throw new InvalidArgumentException('Contenido inválido.');
        }

        if (isset($payload['exp']) && is_numeric($payload['exp']) && (int) $payload['exp'] < time()) {
            throw new InvalidArgumentException('Token vencido.');
        }

        return $payload;
    }

    private static function b64(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private static function unb64(string $data): string
    {
        $remainder = strlen($data) % 4;
        if ($remainder) {
            $data .= str_repeat('=', 4 - $remainder);
        }

        $decoded = base64_decode(strtr($data, '-_', '+/'), true);

        return $decoded === false ? '' : $decoded;
    }
}
