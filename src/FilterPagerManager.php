<?php

namespace Verclam\DoctrineFilterPaginator;

use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;
use Verclam\DoctrineFilterPaginator\Interfaces\FilterDTOInterface;
use Verclam\DoctrineFilterPaginator\QueryGenerator\QueryGeneratorsContainer;

readonly class FilterPagerManager
{
    public function __construct(
        private QueryGeneratorsContainer $queryGeneratorsContainer,
        private ManagerRegistry $registry,
        private string $defaultTotalRecordsKey = 'totalRecords',
        private string $defaultResultsKey = 'results',
    ) {}

    private function initQueryBuilder(
        string $className,
        string $alias,
    ): QueryBuilder {
        return $this->registry->getManagerForClass($className)
            ->createQueryBuilder() // @phpstan-ignore method.notFound
            ->select($alias)
            ->from($className, $alias);
    }

    private function generateAlias(string $className): string
    {
        if (!class_exists($className)) {
            throw new \LogicException(sprintf('The class "%s" is incorrect', $className));
        }

        $entityNameParts = explode('\\', $className);

        return end($entityNameParts);
    }

    /**
     * @throws \ReflectionException
     * @throws \Exception
     */
    public function getResult(?FilterDTOInterface $filterDTO, ?string $className = null): array
    {
        if ($filterDTO?->getCustomSelectDQL()) {
            $queryBuilder = $this->initQueryBuilder($filterDTO::CLASS_NAME, $filterDTO::ALIAS);
            $queryBuilder = $this->filter($queryBuilder, $filterDTO);
            $queryBuilder->select($filterDTO->getCustomSelectDQL());

            return $queryBuilder->getQuery()->getResult();
        }

        if ($filterDTO instanceof FilterDTOInterface) {
            return $this->getPaginatedResult($filterDTO);
        }

        if (is_string($className)) {
            return $this->getAllResult($className);
        }

        throw new \Exception('You must provide either the classname or a FilterDTOInterface');
    }

    private function getAllResult(string $className): array
    {
        $alias = $this->generateAlias($className);
        $queryBuilder = $this->initQueryBuilder($className, $alias);

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * @throws \ReflectionException
     * @throws \Exception
     */
    private function getPaginatedResult(FilterDTOInterface $filterDTO): array
    {
        $queryBuilder = $this->initQueryBuilder($filterDTO::CLASS_NAME, $filterDTO::ALIAS);
        $queryBuilder = $this->filter($queryBuilder, $filterDTO);

        $result = new Paginator($queryBuilder->getQuery());

        $totalRecordsKey = $filterDTO->getTotalRecordsKey() ?? $this->defaultTotalRecordsKey;
        $resultsKey = $filterDTO->getResultsKey() ?? $this->defaultResultsKey;

        return [
            $totalRecordsKey => $result->count(),
            $resultsKey      => $result->getIterator(),
        ];
    }

    private function filter(QueryBuilder $queryBuilder, FilterDTOInterface $filterDTO): QueryBuilder
    {
        $properties = $this->getProperties($filterDTO);
        /** @var \ReflectionProperty $property */
        foreach ($properties as $property) {
            if (!$property->isInitialized($filterDTO)) {
                continue;
            }

            $queryGenerators = $this->queryGeneratorsContainer->getQueryGenerators();
            foreach ($queryGenerators as $queryGenerator) {
                if ($queryGenerator->supports($property->getAttributes())) {
                    $queryGenerator->generateQuery($property, $filterDTO, $queryBuilder);
                }
            }
        }

        return $queryBuilder;
    }

    private function getProperties(FilterDTOInterface $filterDTO): array
    {
        $reflextionClass = new \ReflectionClass($filterDTO);
        $properties = $reflextionClass->getProperties();

        if ($parentReflexionClass = $reflextionClass->getParentClass()) {
            $parentProperties = $parentReflexionClass->getProperties();
            $properties = array_merge($properties, $parentProperties);
        }

        return $properties;
    }

    public function clear(): void
    {
        $this->queryGeneratorsContainer->clear();
    }
}
