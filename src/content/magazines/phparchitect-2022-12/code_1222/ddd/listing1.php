final class CountEventsCommand extends Command
{
  public function execute(
      Arguments $args,
      ConsoleIo $io
  ): ?int {
    $service = CountEventsFactory::countEvents();
    $service->insertCurrentCount();
    $service->insertCurrentCount();
    $io->out('Count complete');

    return 0;
  }
}