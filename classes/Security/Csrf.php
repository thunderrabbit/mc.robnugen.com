<?php

declare(strict_types=1);

namespace Security;

/**
 * One synchronizer token per session, for the forms that change state.
 * The token is minted on first use and kept in the session. A page that
 * handles a POST calls isValid() with what the form sent, and a form writes
 * <?= $csrf->field() ?> inside itself.
 */
final class Csrf
{
    public const string FIELD = 'csrf_token';

    /** @param array<mixed> $session usually $_SESSION */
    public function __construct(private array &$session)
    {
    }

    public function token(): string
    {
        $token = $this->session[self::FIELD] ?? null;
        if (!is_string($token) || $token === '') {
            $token = bin2hex(random_bytes(32));
            $this->session[self::FIELD] = $token;
        }
        return $token;
    }

    /** Timing-safe. No stored token, or a submitted value that is not a string, fails. */
    public function isValid(mixed $submitted): bool
    {
        $stored = $this->session[self::FIELD] ?? null;
        return is_string($stored) && $stored !== '' && is_string($submitted) && hash_equals($stored, $submitted);
    }

    public function field(): string
    {
        return '<input type="hidden" name="' . self::FIELD . '" value="'
            . htmlspecialchars($this->token(), ENT_QUOTES, 'UTF-8') . '">';
    }
}
