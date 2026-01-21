<?php

declare(strict_types=1);

namespace Webard\Biloquent\Groups;

use Illuminate\Contracts\Database\Query\Expression;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Grammars\Grammar;
use Illuminate\Support\Facades\DB;
use Webard\Biloquent\Concerns\HasRelation;

/**
 * Group by a related model's attribute.
 *
 * This group automatically joins the related table and
 * allows grouping by a display column from that table.
 */
class RelationGroup extends Group
{
    use HasRelation;

    protected ?string $displayColumn = null;

    protected string $joinType = 'left';

    protected ?string $relatedTable = null;

    /**
     * Set the column to display from the related table.
     */
    public function displayColumn(string $column): static
    {
        $this->displayColumn = $column;

        return $this;
    }

    /**
     * Set the join type (left or inner).
     *
     * @param  'left'|'inner'  $type
     */
    public function joinType(string $type): static
    {
        $this->joinType = $type;

        return $this;
    }

    /**
     * Set the related table name.
     */
    public function table(string $table): static
    {
        $this->relatedTable = $table;

        return $this;
    }

    /**
     * Build the group expression.
     */
    protected function buildExpression(Grammar $grammar): Expression
    {
        $column = $this->displayColumn ?? $this->getForeignKey();

        // Use the column alias from the dataset CTE
        $columnAlias = $this->getDisplayColumnAlias();

        return DB::raw($grammar->wrap($columnAlias));
    }

    /**
     * Get the alias for the display column in the CTE.
     */
    protected function getDisplayColumnAlias(): string
    {
        if ($this->displayColumn !== null) {
            return str_replace('.', '_', $this->displayColumn);
        }

        return $this->getColumnAlias();
    }

    /**
     * Apply joins to the dataset query.
     *
     * @param  Builder<\Illuminate\Database\Eloquent\Model>  $dataset
     */
    public function applyToDataset(Builder $dataset): void
    {
        if ($this->displayColumn === null) {
            // No display column, just select the foreign key
            return;
        }

        $foreignKey = $this->getForeignKey();
        $ownerKey = $this->getOwnerKey();
        $table = $this->relatedTable;

        if ($foreignKey === null || $ownerKey === null || $table === null) {
            throw new \RuntimeException(
                "RelationGroup '{$this->name}' requires foreignKey(), ownerKey(), and table() to be set when using displayColumn()."
            );
        }

        // Apply the join
        $joinMethod = $this->joinType === 'inner' ? 'join' : 'leftJoin';

        $dataset->{$joinMethod}($table, $foreignKey, '=', $ownerKey);
    }

    /**
     * Apply any necessary joins to the report query.
     *
     * @param  Builder<\Illuminate\Database\Eloquent\Model>  $query
     */
    public function applyJoins(Builder $query): void
    {
        // Joins are applied at the dataset level
    }

    /**
     * Get the columns that need to be selected in the dataset.
     *
     * @return array<string>
     */
    public function getDatasetColumns(): array
    {
        $columns = [];

        // Always include the foreign key
        $foreignKey = $this->getForeignKey();
        if ($foreignKey !== null) {
            $columns[] = $foreignKey.' as '.$this->getForeignKeyAlias();
        }

        // Include the display column if set
        if ($this->displayColumn !== null) {
            $columns[] = $this->displayColumn.' as '.$this->getDisplayColumnAlias();
        }

        return $columns;
    }

    /**
     * Get the alias for the foreign key column.
     */
    protected function getForeignKeyAlias(): string
    {
        $foreignKey = $this->getForeignKey();

        if ($foreignKey === null) {
            return $this->getName().'_fk';
        }

        return str_replace('.', '_', $foreignKey);
    }
}
