<?php

declare(strict_types=1);

namespace Webard\Biloquent\Concerns;

use Illuminate\Contracts\Database\Query\Expression;

trait HasColumn
{
    protected string|Expression|null $column = null;

    protected ?string $columnAlias = null;

    /**
     * Set the column for this aggregator/group.
     */
    public function column(string|Expression $column): static
    {
        $this->column = $column;

        return $this;
    }

    /**
     * Get the column.
     */
    public function getColumn(): string|Expression|null
    {
        return $this->column;
    }

    /**
     * Get the column alias used in the dataset CTE.
     */
    public function getColumnAlias(): string
    {
        if ($this->columnAlias !== null) {
            return $this->columnAlias;
        }

        if ($this->column instanceof Expression) {
            return $this->getName().'_col';
        }

        // Convert "orders.created_at" to "orders_created_at"
        return str_replace('.', '_', (string) $this->column);
    }

    /**
     * Set a custom column alias.
     */
    public function columnAlias(string $alias): static
    {
        $this->columnAlias = $alias;

        return $this;
    }
}
