<?php

namespace Verclam\DoctrineFilterPaginator\Attributes;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::IS_REPEATABLE)]
class LeftJoin implements FilterPagerAttributesInterface
{
    public array $joins;

    /**
     * @throws \Exception
     */
    public function __construct(
        string|array $join,
        public bool $withVersion = false,
        public ?string $className = null,
    ) {
        $this->joins = is_array($join) ? $join : [$join];

        if ($this->withVersion) {
            $this->validateAttribute();
        }
    }

    /**
     * @throws \Exception
     */
    private function validateAttribute(): void
    {
        if (!$this->className) {
            throw new \Exception('className is required when withVersion is true');
        }
    }
}
