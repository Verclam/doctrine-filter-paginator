<?php

namespace Verclam\DoctrineFilterPaginator\QueryGenerator;

use Doctrine\ORM\QueryBuilder;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Verclam\DoctrineFilterPaginator\Attributes\Pager;
use Verclam\DoctrineFilterPaginator\Interfaces\FilterDTOInterface;

#[AutoconfigureTag(name: self::SERVICE_TAG, attributes: ['key' => self::class])]
class PagerQueryGenerator implements QueryGeneratorInterface
{
    public function supports(array $attributes): bool
    {
        foreach ($attributes as $attribute) {
            if (Pager::class === $attribute->getName()) {
                return true;
            }
        }

        return false;
    }

    /**
     * @throws \Exception
     */
    public function generateQuery(
        \ReflectionProperty $reflectionProperty,
        FilterDTOInterface $filtersDTO,
        QueryBuilder $queryBuilder,
    ): void {
        try {
            $rows = $filtersDTO->getRows();
            $page = $filtersDTO->getPage();

            $queryBuilder
                ->setFirstResult($rows * $page)
                ->setMaxResults($rows);
        } catch (\Exception $e) {
            throw new \Exception('Pager query generator error');
        }
    }

    public function clear(): void
    {
        // TODO: Implement clear() method.
    }
}
