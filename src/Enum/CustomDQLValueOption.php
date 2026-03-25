<?php

namespace Verclam\DoctrineFilterPaginator\Enum;

enum CustomDQLValueOption: string
{
    case IGNORE_VALUE = 'ignore_value';
    case VALUE_AS_PARAMETER = 'value_as_parameter';
}
