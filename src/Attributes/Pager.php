<?php

namespace Verclam\DoctrineFilterPaginator\Attributes;

#[\Attribute(\Attribute::TARGET_PROPERTY)]
class Pager implements FilterPagerAttributesInterface
{
    public const PAGE_METHOD_NAME = 'getPage';
    public const ROWS_METHOD_NAME = 'getRows';

    public function __construct() {}
}
