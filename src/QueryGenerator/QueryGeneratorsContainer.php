<?php

namespace Verclam\DoctrineFilterPaginator\QueryGenerator;

use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

class QueryGeneratorsContainer
{
    /**
     * @var QueryGeneratorInterface[]
     */
    private array $queryGenerators = [];

    public function __construct(
        #[AutowireIterator(QueryGeneratorInterface::SERVICE_TAG, indexAttribute: 'key')]
        iterable $services,
    ) {
        $this->setServices($services);
    }

    public function setServices(iterable $services): void
    {
        $this->queryGenerators = iterator_to_array($services);
    }

    /**
     * @return QueryGeneratorInterface[]
     */
    public function getQueryGenerators(): array
    {
        return $this->queryGenerators;
    }

    public function clear(): void
    {
        foreach ($this->queryGenerators as $queryGenerator) {
            $queryGenerator->clear();
        }
    }
}
