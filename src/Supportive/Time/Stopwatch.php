<?php

declare(strict_types=1);

namespace Qossmic\Deptrac\Supportive\Time;

use function array_key_exists;

final class Stopwatch
{
    /** @var array<non-empty-string, StartedPeriod> */
    private array $periods = [];

    /**
     * @param non-empty-string $event
     *
     * @throws StopwatchException
     */
    public function start(string $event): void
    {
        $this->assertPeriodNotStarted($event);

        $this->periods[$event] = StartedPeriod::start();
    }

    /**
     * @param non-empty-string $event
     *
     * @throws StopwatchException
     */
    public function stop(string $event): Period
    {
        $this->assertPeriodStarted($event);

        $period = $this->periods[$event]->stop();

        unset($this->periods[$event]);

        return $period;
    }

    /**
     * @param non-empty-string $event
     *
     * @throws StopwatchException
     */
    private function assertPeriodNotStarted(string $event): void
    {
        if (array_key_exists($event, $this->periods)) {
            throw StopwatchException::periodAlreadyStarted($event);
        }
    }

    /**
     * @param non-empty-string $event
     *
     * @throws StopwatchException
     */
    private function assertPeriodStarted(string $event): void
    {
        if (!array_key_exists($event, $this->periods)) {
            throw StopwatchException::periodNotStarted($event);
        }
    }
}
