use Appointments\Appointment;
use Appointments\MockAppointmentClock;
use PHPUnit\Framework\TestCase;

class SendFollowUpNotificationTest extends TestCase
{
    /**
     * @test
     */
    public function testItShouldSendAFollowUp()
    {
        $appointmentClock = new MockAppointmentClock(
          (new \DateTimeImmutable())->modify('-1 hour')
        );

        $appointment = new Appointment(
						$appointmentClock, 
						$appointmentClock->now()
        );

        $appointment->setFinishedDateTime(
          $appointmentClock->now()->modify('+ 1 hour')
        );

        $this->assertTrue(
            $appointment->shouldSendFollowUp(
                $appointmentClock->now()
							     ->modify('+14 days')
							     ->modify('+1 hour')
            )
        );
    }
}