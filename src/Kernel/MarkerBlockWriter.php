<?php

declare(strict_types=1);

namespace CleanArchitecture\Kernel;

/**
 * Inserts generated lines between `// {marker}` ... `// {/marker}` comment
 * pairs, dropping placeholder comments and blank lines already inside the
 * block while preserving previously inserted code.
 */
final class MarkerBlockWriter
{
    /**
     * @return string|null The updated content, or null when the markers could
     *                     not be processed (PCRE failure) — callers must treat
     *                     null as "leave the file untouched".
     */
    public static function insert(string $content, string $marker, string $insertion): ?string
    {
        $pattern = '/([ \t]*\/\/ \{' . preg_quote($marker, '/') . '\}\n)(.*?)([ \t]*\/\/ \{\/' . preg_quote($marker, '/') . '\})/s';

        return preg_replace_callback(
            $pattern,
            function (array $matches) use ($insertion): string {
                // Keep only real code lines (remove TODO comments and blank lines)
                $kept = [];

                foreach (explode("\n", $matches[2]) as $line) {
                    $trimmed = trim($line);

                    if ($trimmed === '' || str_starts_with($trimmed, '//')) {
                        continue;
                    }

                    $kept[] = $line;
                }

                $existing = implode("\n", $kept);
                $result = $matches[1];

                if ($existing !== '') {
                    $result .= $existing . "\n";
                }

                return $result . $insertion . "\n" . $matches[3];
            },
            $content
        );
    }
}
