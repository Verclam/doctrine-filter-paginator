<?php

namespace Verclam\DoctrineFilterPaginator\QueryGenerator;

use Doctrine\ORM\QueryBuilder;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Verclam\DoctrineFilterPaginator\Attributes\LeftJoin;
use Verclam\DoctrineFilterPaginator\Interfaces\FilterDTOInterface;

#[AutoconfigureTag(name: self::SERVICE_TAG, attributes: ['key' => self::class])]
class LeftJoinQueryGenerator implements QueryGeneratorInterface
{
    private array $alreadyJoinedTable = [];

    public function supports(array $attributes): bool
    {
        foreach ($attributes as $attribute) {
            if (LeftJoin::class === $attribute->getName()) {
                return true;
            }
        }

        return false;
    }

    private function getAttributes(\ReflectionProperty $reflectionProperty): array
    {
        $reflectionAttributes = [];
        foreach ($reflectionProperty->getAttributes() as $attribute) {
            if (LeftJoin::class === $attribute->getName()) {
                $reflectionAttributes[] = $attribute->newInstance();
            }
        }

        return $reflectionAttributes;
    }

    /**
     * @throws \Exception
     */
    public function generateQuery(
        \ReflectionProperty $reflectionProperty,
        FilterDTOInterface $filtersDTO,
        QueryBuilder $queryBuilder,
    ): void {
        foreach ($this->getAttributes($reflectionProperty) as $reflectionAttribute) {
            $alias = $queryBuilder->getRootAliases()[0];
            $this->generateLeftJoin($alias, $queryBuilder, $reflectionAttribute);
        }
    }

    private function generateLeftJoin(
        string $alias,
        QueryBuilder $queryBuilder,
        LeftJoin $leftJoin,
    ): void {
        $joins = $leftJoin->joins;

        match ($leftJoin->withVersion) {
            true  => $this->generateLeftJoinWith($alias, $queryBuilder, $leftJoin),
            false => $this->generateLeftJoinSimple($alias, $queryBuilder, $leftJoin),
        };
    }

    private function generateLeftJoinWith(
        string $alias,
        QueryBuilder $queryBuilder,
        LeftJoin $leftJoin,
    ) {
        $joins = $leftJoin->joins;

        foreach ($joins as $key => $join) {
            if (!$this->isAlreadyJoined($join)) {
                if (0 === $key) {
                    $queryBuilder->leftJoin($leftJoin->className, $join, 'WITH', "$join.id = $alias.id");
                } else {
                    $newJoin = $alias.ucfirst($join);

                    if (!$this->isAlreadyJoined($newJoin)) {
                        $queryBuilder->leftJoin($alias.'.'.$join, $newJoin);
                    }

                    $join = $newJoin;
                }
            }

            $alias = $join;
            $this->addJoinedTable($join);
        }
    }

    private function generateLeftJoinSimple(
        string $alias,
        QueryBuilder $queryBuilder,
        LeftJoin $leftJoin,
    ) {
        $joins = $leftJoin->joins;

        foreach ($joins as $key => $join) {
            if (!$this->isAlreadyJoined($join)) {
                $queryBuilder->leftJoin($alias.'.'.$join, $join);
            }
            $alias = $join;
            $this->addJoinedTable($join);
        }
    }

    private function isAlreadyJoined(string $relationName): bool
    {
        return array_key_exists($relationName, $this->alreadyJoinedTable);
    }

    private function addJoinedTable(string $relationName): void
    {
        $this->alreadyJoinedTable[$relationName] = true;
    }

    public function clear(): void
    {
        $this->alreadyJoinedTable = [];
    }
}
