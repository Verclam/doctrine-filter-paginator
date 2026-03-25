<?php

namespace Verclam\DoctrineFilterPaginator\QueryGenerator;

use Doctrine\ORM\QueryBuilder;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Verclam\DoctrineFilterPaginator\Attributes\OrderBy;
use Verclam\DoctrineFilterPaginator\Interfaces\FilterDTOInterface;

#[AutoconfigureTag(name: self::SERVICE_TAG, attributes: ['key' => self::class])]
class OrderByQueryGenerator implements QueryGeneratorInterface
{
    public function supports(array $attributes): bool
    {
        foreach ($attributes as $attribute) {
            if (OrderBy::class === $attribute->getName()) {
                return true;
            }
        }

        return false;
    }

    private function getAttributes(\ReflectionProperty $reflectionProperty): array
    {
        $reflectionAttributes = [];
        foreach ($reflectionProperty->getAttributes() as $attribute) {
            if (OrderBy::class === $attribute->getName()) {
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
        foreach ($this->getAttributes($reflectionProperty) as $orderByAttribute) {
            /* @var OrderBy $orderByAttribute */
            $propertyName = $orderByAttribute->property;
            $value = $reflectionProperty->getValue($filtersDTO);
            $alias = $orderByAttribute->joined ? '' : $queryBuilder->getRootAliases()[0].'.';
            $queryBuilder->orderBy($alias.$propertyName, $value);
        }
    }

    public function clear(): void
    {
        // TODO: Implement clear() method.
    }
}
