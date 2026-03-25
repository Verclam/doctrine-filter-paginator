<?php

namespace Verclam\DoctrineFilterPaginator\QueryGenerator;

use Doctrine\ORM\QueryBuilder;
use Verclam\DoctrineFilterPaginator\Interfaces\FilterDTOInterface;

interface QueryGeneratorInterface
{
    public const SERVICE_TAG = 'filter_pager.query_generator';

    /**
     * @param \ReflectionAttribute[] $attributes
     */
    public function supports(array $attributes): bool;

    public function generateQuery(
        \ReflectionProperty $reflectionProperty,
        FilterDTOInterface $filtersDTO,
        QueryBuilder $queryBuilder,
    ): void;

    public function clear(): void;
}
