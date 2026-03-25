<?php

namespace Verclam\DoctrineFilterPaginator\Utils;

class DateUtils
{
    private \DateTimeZone $dateTimeZone;

    public function __construct(
        string $timezone = 'Europe/Paris',
    ) {
        $this->dateTimeZone = new \DateTimeZone($timezone);
    }

    public function today(): \DateTime
    {
        $today = new \DateTime();

        return $today->setTimezone($this->dateTimeZone);
    }

    public function setTimeZone(?string $strDate): ?\DateTime
    {
        try {
            if (empty($strDate)) {
                return null;
            }

            $date = new \DateTime($strDate);

            return $date->setTimezone($this->dateTimeZone);
        } catch (\Exception) {
            return null;
        }
    }
}
