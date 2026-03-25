<?php

namespace Verclam\DoctrineFilterPaginator\Enum;

enum FilterCondition: string
{
    case AND = 'andWhere';
    case OR = 'orWhere';
}
