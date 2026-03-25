<?php

namespace Verclam\DoctrineFilterPaginator\QueryGenerator;

use Doctrine\ORM\QueryBuilder;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Verclam\DoctrineFilterPaginator\Attributes\FilterBy;
use Verclam\DoctrineFilterPaginator\Enum\CustomDQLValueOption;
use Verclam\DoctrineFilterPaginator\Interfaces\FilterDTOInterface;
use Verclam\DoctrineFilterPaginator\Utils\DateUtils;

#[AutoconfigureTag(name: self::SERVICE_TAG, attributes: ['key' => self::class])]
class FilterByQueryGenerator implements QueryGeneratorInterface
{
    public function __construct(
        private readonly DateUtils $dateUtils,
    ) {}

    public function supports(array $attributes): bool
    {
        foreach ($attributes as $attribute) {
            if (FilterBy::class === $attribute->getName()) {
                return true;
            }
        }

        return false;
    }

    private function getAttributes(\ReflectionProperty $reflectionProperty): array
    {
        $reflectionAttributes = [];
        foreach ($reflectionProperty->getAttributes() as $attribute) {
            if (FilterBy::class === $attribute->getName()) {
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
        foreach ($this->getAttributes($reflectionProperty) as $filterByAttribute) {
            $propertyName = $reflectionProperty->getName();
            $filterByValues = $reflectionProperty->getValue($filtersDTO);

            if ('__null__' === $filterByValues) {
                $filterByValues = null;
            }

            match ($filterByAttribute->operator) {
                FilterBy::EQUAL, FilterBy::NOT_EQUAL, FilterBy::GREATER_THAN_OR_EQUAL, FilterBy::GREATER_THAN,
                FilterBy::LESS_THAN_OR_EQUAL, FilterBy::LESS_THAN, => $this->generateStandard($filterByAttribute, $propertyName, $filterByValues, $queryBuilder),
                FilterBy::MEMBER_OF => $this->membersOf(
                    $filterByAttribute,
                    $propertyName,
                    $filterByValues,
                    $queryBuilder,
                ),
                FilterBy::IN => $this->in(
                    $filterByAttribute,
                    $propertyName,
                    $filterByValues,
                    $queryBuilder,
                ),
                FilterBy::IS_NULL          => $this->generateIsNull($filterByAttribute, $queryBuilder),
                FilterBy::BETWEEN          => $this->generateBetween($filterByAttribute, $propertyName, $filterByValues, $queryBuilder),
                FilterBy::CUSTOM_CONDITION => $this->generateCalculated($filterByAttribute, $filterByValues, $propertyName, $queryBuilder),
                FilterBy::LIKE             => $this->generateLike($filterByAttribute, $propertyName, $filterByValues, $queryBuilder),
                default                    => throw new \Exception('Not supported filter by type'),
            };
        }
    }

    private function generateStandard(
        FilterBy $filterBy,
        string $propertyName,
        array|string|null $filterByValues,
        QueryBuilder $queryBuilder,
    ): void {
        $alias = $filterBy->joined ? '' : $queryBuilder->getRootAliases()[0].'.';
        $filterCondition = $filterBy->filterCondition->value;

        $queryBuilder->$filterCondition(
            $alias.$filterBy->property.$filterBy->negation.$filterBy->operator
            .$filterBy->prefix.$propertyName.$filterBy->suffix,
        )
            ->setParameter($propertyName, $filterByValues);
    }

    private function membersOf(
        FilterBy $filterBy,
        string $propertyName,
        array|string|null $filterByValues,
        QueryBuilder $queryBuilder,
    ): void {
        $alias = $filterBy->joined ? '' : $queryBuilder->getRootAliases()[0].'.';
        $filterCondition = $filterBy->filterCondition->value;

        $queryBuilder->$filterCondition(
            ':'.$propertyName.' '.$filterBy->negation
            .$filterBy->operator.$alias.$filterBy->property,
        )
            ->setParameter($propertyName, (array) $filterByValues);
    }

    private function in(
        FilterBy $filterBy,
        string $propertyName,
        array|string|null $filterByValues,
        QueryBuilder $queryBuilder,
    ): void {
        if (!$filterByValues) {
            return;
        }

        $alias = $filterBy->joined ? '' : $queryBuilder->getRootAliases()[0].'.';
        $filterCondition = $filterBy->filterCondition->value;

        $queryBuilder->$filterCondition(
            $alias.$filterBy->property
            .' '.$filterBy->negation.$filterBy->operator
            .'(:'.$propertyName.')',
        )
            ->setParameter($propertyName, (array) $filterByValues);
    }

    private function generateLike(
        FilterBy $filterBy,
        string $propertyName,
        string $filterByValues,
        QueryBuilder $queryBuilder,
    ): void {
        $alias = $filterBy->joined ? '' : $queryBuilder->getRootAliases()[0].'.';
        $filterCondition = $filterBy->filterCondition->value;

        $queryBuilder->$filterCondition(
            $alias.$filterBy->property.$filterBy->negation.$filterBy->operator.$propertyName,
        )
            ->setParameter($propertyName, $filterBy->prefix.$filterByValues.$filterBy->suffix);
    }

    /**
     * @throws \Exception
     */
    private function generateBetween(
        FilterBy $filterBy,
        string $propertyName,
        ?string $filterByValues,
        QueryBuilder $queryBuilder,
    ): void {
        try {
            $filterByValues = json_decode($filterByValues, true, 512, JSON_THROW_ON_ERROR);
        } catch (\Exception) {
            throw new \Exception('Filter by between must be a json string');
        }

        if (FilterBy::DATA_TYPES_DATE === $filterBy->dataTypes) {
            $filterByValues[0] = $this->dateUtils->setTimeZone($filterByValues[0]);
            $filterByValues[1] = $this->dateUtils->setTimeZone($filterByValues[1]);
        }

        $alias = $filterBy->joined ? '' : $queryBuilder->getRootAliases()[0].'.';

        $filterCondition = $filterBy->filterCondition->value;

        $queryBuilder->$filterCondition(
            $alias.$filterBy->property.$filterBy->negation.$filterBy->operator
            .$filterBy->prefix.$propertyName.'Min'.$filterBy->suffix.$propertyName.'Max',
        )
            ->setParameter($propertyName.'Min', $filterByValues[0])
            ->setParameter($propertyName.'Max', $filterByValues[1]);
    }

    private function generateCalculated(
        FilterBy $filterBy,
        string $filterByValues,
        string $propertyName,
        QueryBuilder $queryBuilder,
    ): void {
        $filterCondition = $filterBy->filterCondition->value;

        if (CustomDQLValueOption::IGNORE_VALUE === $filterBy->dqlOptions) {
            $filterByValues = '';
        }

        if (CustomDQLValueOption::VALUE_AS_PARAMETER === $filterBy->dqlOptions) {
            $queryBuilder->setParameter($propertyName, $filterByValues);
            $filterByValues = '';
        }

        $queryBuilder->$filterCondition($filterBy->property.' '.$filterBy->operator.$filterByValues);
    }

    public function clear(): void
    {
        // TODO: Implement clear() method.
    }

    private function generateIsNull(
        FilterBy $filterBy,
        QueryBuilder $queryBuilder,
    ): void {
        $alias = $filterBy->joined ? '' : $queryBuilder->getRootAliases()[0].'.';
        $filterCondition = $filterBy->filterCondition->value;

        $queryBuilder->$filterCondition(
            $alias.$filterBy->property.$filterBy->negation.$filterBy->operator,
        );
    }
}
