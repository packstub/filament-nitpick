<?php

namespace Packstub\Nitpick\Support;

/**
 * What a plugin works out once per request, kept in a scoped binding so it
 * ends with the request. Filament keeps its panels, and so their plugins,
 * for the life of the process: a property on the plugin would outlive the
 * request under Octane, which forgets scoped bindings between requests,
 * and serve a user the theme they had on an earlier one.
 */
class RequestMemo
{
    /** @var array<string, mixed> */
    protected array $values = [];

    public static function current(): static
    {
        return app(static::class);
    }

    public function remember(string $key, \Closure $resolve): mixed
    {
        if (! array_key_exists($key, $this->values)) {
            $this->values[$key] = $resolve();
        }

        return $this->values[$key];
    }

    public function put(string $key, mixed $value): void
    {
        $this->values[$key] = $value;
    }

    /**
     * Forget every key that starts with $prefix.
     */
    public function forget(string $prefix): void
    {
        foreach (array_keys($this->values) as $key) {
            if (str_starts_with($key, $prefix)) {
                unset($this->values[$key]);
            }
        }
    }
}
