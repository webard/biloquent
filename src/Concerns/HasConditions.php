<?php

declare(strict_types=1);

namespace Webard\Biloquent\Concerns;

use Closure;

trait HasConditions
{
    protected bool $visible = true;

    /**
     * @var array<array{condition: mixed, callback: Closure}>
     */
    protected array $conditions = [];

    /**
     * Conditionally apply a callback to this aggregator/group.
     */
    public function when(mixed $condition, ?Closure $callback = null): static
    {
        // If only condition is passed (no callback), use it as visibility toggle
        if ($callback === null) {
            $this->visible = (bool) (is_callable($condition) ? $condition() : $condition);

            return $this;
        }

        // If condition is true, apply the callback
        $result = is_callable($condition) ? $condition() : $condition;

        if ($result) {
            $callback($this);
        }

        return $this;
    }

    /**
     * Mark this aggregator/group as visible.
     */
    public function visible(): static
    {
        $this->visible = true;

        return $this;
    }

    /**
     * Mark this aggregator/group as hidden.
     */
    public function hidden(): static
    {
        $this->visible = false;

        return $this;
    }

    /**
     * Check if this aggregator/group is visible.
     */
    public function isVisible(): bool
    {
        return $this->visible;
    }
}
