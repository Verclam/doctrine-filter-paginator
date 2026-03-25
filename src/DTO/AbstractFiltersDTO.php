<?php

namespace Verclam\DoctrineFilterPaginator\DTO;

use Verclam\DoctrineFilterPaginator\Attributes\Pager;
use Verclam\DoctrineFilterPaginator\Interfaces\FilterDTOInterface;

abstract class AbstractFiltersDTO implements FilterDTOInterface
{
    protected ?string $customSelectDQL = null;

    private ?string $totalRecordsKey = null;

    private ?string $resultsKey = null;

    #[Pager]
    private int $page;

    private int $rows;

    public function getPage(): int
    {
        return $this->page;
    }

    public function setPage(int $page): void
    {
        $this->page = $page;
    }

    public function getRows(): int
    {
        return $this->rows;
    }

    public function setRows(int $rows): void
    {
        $this->rows = $rows;
    }

    public function getCustomSelectDQL(): ?string
    {
        return $this->customSelectDQL;
    }

    public function getTotalRecordsKey(): ?string
    {
        return $this->totalRecordsKey;
    }

    public function setTotalRecordsKey(string $totalRecordsKey): void
    {
        $this->totalRecordsKey = $totalRecordsKey;
    }

    public function getResultsKey(): ?string
    {
        return $this->resultsKey;
    }

    public function setResultsKey(string $resultsKey): void
    {
        $this->resultsKey = $resultsKey;
    }
}
