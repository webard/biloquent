<?php

declare(strict_types=1);

namespace Webard\Biloquent\Concerns;

trait HasRelation
{
    protected ?string $relation = null;

    protected ?string $foreignKey = null;

    protected ?string $ownerKey = null;

    protected ?string $relationAggregator = null;

    /**
     * Set the relation name for this aggregator/group.
     */
    public function relation(string $relation): static
    {
        $this->relation = $relation;

        return $this;
    }

    /**
     * Check if this uses a relation.
     */
    public function hasRelation(): bool
    {
        return $this->relation !== null;
    }

    /**
     * Get the relation name.
     */
    public function getRelation(): ?string
    {
        return $this->relation;
    }

    /**
     * Set the foreign key for the relation.
     */
    public function foreignKey(string $foreignKey): static
    {
        $this->foreignKey = $foreignKey;

        return $this;
    }

    /**
     * Get the foreign key.
     */
    public function getForeignKey(): ?string
    {
        return $this->foreignKey;
    }

    /**
     * Set the owner key for the relation.
     */
    public function ownerKey(string $ownerKey): static
    {
        $this->ownerKey = $ownerKey;

        return $this;
    }

    /**
     * Get the owner key.
     */
    public function getOwnerKey(): ?string
    {
        return $this->ownerKey;
    }

    /**
     * Set the aggregator function to use for the relation.
     * This is used for relation-based aggregators where you first
     * aggregate at the relation level, then aggregate those results.
     */
    public function aggregator(string $aggregator): static
    {
        $this->relationAggregator = $aggregator;

        return $this;
    }

    /**
     * Get the relation aggregator.
     */
    public function getRelationAggregator(): ?string
    {
        return $this->relationAggregator;
    }
}
