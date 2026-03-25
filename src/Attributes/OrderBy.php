<?php

namespace Verclam\DoctrineFilterPaginator\Attributes;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::IS_REPEATABLE)]
class OrderBy implements FilterPagerAttributesInterface
{
    public const ASC = 'ASC';
    public const DESC = 'DESC';

    public function __construct(public string $property, public bool $joined = false) {}
}
