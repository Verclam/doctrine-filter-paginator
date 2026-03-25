<?php

namespace Verclam\DoctrineFilterPaginator\Attributes;

use Verclam\DoctrineFilterPaginator\Enum\CustomDQLValueOption;
use Verclam\DoctrineFilterPaginator\Enum\FilterCondition;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::IS_REPEATABLE)]
class FilterBy implements FilterPagerAttributesInterface
{
    public const string NAME = 'FilterBy';
    public const string EQUAL = '= ';
    public const string GREATER_THAN = '> ';
    public const string GREATER_THAN_OR_EQUAL = '>= ';
    public const string LESS_THAN = '< ';
    public const string LESS_THAN_OR_EQUAL = '<= ';
    public const string LIKE = 'LIKE :';
    public const string NOT_EQUAL = '!=';
    public const string NOT = 'NOT ';

    public const string IN = 'IN ';
    public const string MEMBER_OF = 'MEMBER OF ';
    public const string IS_NULL = 'IS NULL ';
    public const string BETWEEN = 'BETWEEN ';
    public const string CUSTOM_CONDITION = '';

    public const string PROPERTY_NAME_PROPERTY = 'property';
    public const string OPERATOR_PROPERTY = 'operator';

    public string $prefix;
    public string $suffix;

    public const string DATA_TYPES_STRING = 'string';
    public const string DATA_TYPES_DATE = 'date';

    public string $dataTypes = self::DATA_TYPES_STRING;
    public string $negation = ' ';
    public bool $joined = false;
    public ?CustomDQLValueOption $dqlOptions = null;
    public FilterCondition $filterCondition;

    public function __construct(
        public string $property,
        public string $operator,
        array $options = [],
    ) {
        match ($this->operator) {
            self::BETWEEN => $this->handleBetweenOperator(),
            self::IN      => $this->handleInOperator(),
            self::LIKE    => $this->handleLikeOperator(),
            self::IS_NULL => $this->handleIsNull(),
            default       => $this->handleOthers(),
        };

        if (array_key_exists('negation', $options)) {
            $this->negation = $options['negation'];
        }

        if (array_key_exists('dataTypes', $options)) {
            $this->dataTypes = $options['dataTypes'];
        }

        if (array_key_exists('joined', $options)) {
            $this->joined = $options['joined'];
        }

        if (array_key_exists('dqlOptions', $options)) {
            if ($options['dqlOptions'] instanceof CustomDQLValueOption) {
                $this->dqlOptions = $options['dqlOptions'];
            } else {
                throw new \InvalidArgumentException('dqlOptions must be an instance of CustomDQLValueOption');
            }
        }

        $this->filterCondition = $options['filterCondition'] ?? FilterCondition::AND;
    }

    private function handleIsNull(): void
    {
        $this->prefix = '';
        $this->suffix = '';
    }

    private function handleInOperator(): void
    {
        $this->prefix = '(:';
        $this->suffix = ')';
    }

    private function handleBetweenOperator(): void
    {
        $this->suffix = ' AND :';
        $this->prefix = ':';
    }

    private function handleLikeOperator(): void
    {
        $this->prefix = '%';
        $this->suffix = '%';
    }

    private function handleOthers(): void
    {
        $this->prefix = ':';
        $this->suffix = '';
    }
}
