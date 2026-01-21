<?php

declare(strict_types=1);

namespace Webard\Biloquent\Concerns;

trait HasLabel
{
    protected ?string $label = null;

    protected ?string $alias = null;

    /**
     * Set the display label for this aggregator/group.
     */
    public function label(string $label): static
    {
        $this->label = $label;

        return $this;
    }

    /**
     * Get the display label.
     */
    public function getLabel(): ?string
    {
        return $this->label;
    }

    /**
     * Set a custom alias for the result column.
     */
    public function as(string $alias): static
    {
        $this->alias = $alias;

        return $this;
    }

    /**
     * Get the alias for the result column.
     */
    public function getAlias(): string
    {
        return $this->alias ?? $this->getName();
    }
}
