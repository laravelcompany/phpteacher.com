
namespace Appointments;

use Psr\Clock\ClockInterface;

class Appointment
{
  protected ?DateTimeImmutable $finishedDateTime;

  public function__construct(
    protected ClockInterface $appointmentClock,
    protected \DateTimeImmutable $appointmentDateTime
  ) { }

  public function setFinishedDateTime(
    \DateTimeImmutable $dateTime
  ) {
    $this->finishedDateTime = $dateTime;
  }

  public function getFinishedDateTime(
  ): ?DateTimeImmutable {
    return $this->finishedDateTime;
  }

  public function shouldSendFollowUp(
    \DateTimeImmutable $currentDateTime
  ): bool {
    if(is_null($this->getFinishedDateTime())) {
    	return false;
    }

    $finishedDate = $this->getFinishedDateTime();
    $followUpDate = $finishedDate->getTimestamp();
    $now          = $currentDateTime->getTimestamp();

    return $now >= $followUpDate;
  }

  //more functionality here
}