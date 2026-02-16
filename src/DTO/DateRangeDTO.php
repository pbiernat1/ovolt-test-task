<?php
declare(strict_types=1);

namespace App\DTO;

final readonly class DateRangeDTO
{
    private const int MAX_DAYS = 7;

    public \DateTimeImmutable $start;
    public \DateTimeImmutable $end;

    public function __construct(string $start, string $end)
    {
        $startDate = \DateTimeImmutable::createFromFormat('Y-m-d', $start);
        $endDate = \DateTimeImmutable::createFromFormat('Y-m-d', $end);

        if ($startDate === false || $endDate === false) {
            throw new \InvalidArgumentException('Invalid date format. Use YYYY-MM-DD.');
        }

        if ($startDate > $endDate) {
            throw new \InvalidArgumentException('Start date must be before end date.');
        }

        if ($endDate->diff($startDate)->days > self::MAX_DAYS) {
            throw new \InvalidArgumentException(
                sprintf('Date range cannot exceed %d days.', self::MAX_DAYS),
            );
        }

        $this->start = $startDate;
        $this->end = $endDate;
    }

    public function formatStart(): string
    {
        return $this->start->format('Y-m-d');
    }

    public function formatEnd(): string
    {
        return $this->end->format('Y-m-d');
    }
}
