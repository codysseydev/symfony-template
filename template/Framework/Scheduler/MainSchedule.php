<?php

declare(strict_types=1);

namespace Framework\Scheduler;

use Symfony\Component\Scheduler\Attribute\AsSchedule;
use Symfony\Component\Scheduler\Schedule;
use Symfony\Component\Scheduler\ScheduleProviderInterface;

/**
 * The one schedule, consumed by the `scheduler` container as the Messenger
 * transport `scheduler_default`. It is empty on purpose: how a module adds to it
 * is decided with the first schedule.
 */
#[AsSchedule]
final class MainSchedule implements ScheduleProviderInterface
{
    public function getSchedule(): Schedule
    {
        return new Schedule();
    }
}
