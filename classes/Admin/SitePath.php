<?php

declare(strict_types=1);

namespace Admin;

/**
 * A link a notification may carry: a path on this site only, so a stored
 * link can never send an admin to another host. It starts with one slash,
 * never two, and has no backslash, scheme or anything else a browser could
 * read as another host.
 */
final class SitePath
{
    public static function isPath(mixed $raw): bool
    {
        return is_string($raw)
            && strlen($raw) <= 500
            && preg_match('#\A/(?![/\\\\])[A-Za-z0-9/._~%?=&+-]*\z#', $raw) === 1;
    }
}
