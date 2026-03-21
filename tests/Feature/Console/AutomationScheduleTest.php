<?php

namespace Tests\Feature\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AutomationScheduleTest extends TestCase
{
    use RefreshDatabase;

    public function test_automation_commands_are_scheduled_daily(): void
    {
        $schedule = app(Schedule::class);
        $events = collect($schedule->events());

        $paymentsEvent = $events->first(fn ($event) => str_contains((string) $event->command, 'app:check-lease-payments'));
        $demeritEvent = $events->first(fn ($event) => str_contains((string) $event->command, 'app:reconcile-demerit-thresholds'));

        $this->assertNotNull($paymentsEvent);
        $this->assertNotNull($demeritEvent);

        $this->assertSame('0 0 * * *', $paymentsEvent->expression);
        $this->assertSame('0 0 * * *', $demeritEvent->expression);
    }
}
