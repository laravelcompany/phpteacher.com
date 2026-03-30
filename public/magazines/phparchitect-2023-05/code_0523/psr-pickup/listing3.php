namespace Appointments;

use Psr\Clock\ClockInterface;

class MockAppointmentClock implements ClockInterface
{
    public function __construct(
        protected \DateTimeImmutable $dateTime
    ) { }

    /**
     * @inheritDoc
     */
    public function now(): \DateTimeImmutable
    {
        return $this->dateTime;
    }
}