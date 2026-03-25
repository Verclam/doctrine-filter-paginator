<?php

namespace Verclam\DoctrineFilterPaginator\Interfaces;

interface FilterDTOInterface
{
    public const CLASS_NAME = '';
    public const ALIAS = '';

    public function getPage(): int;

    public function setPage(int $page): void;

    public function getRows(): int;

    public function setRows(int $rows): void;

    public function getCustomSelectDQL(): ?string;

    public function getTotalRecordsKey(): ?string;

    public function setTotalRecordsKey(string $totalRecordsKey): void;

    public function getResultsKey(): ?string;

    public function setResultsKey(string $resultsKey): void;
}
