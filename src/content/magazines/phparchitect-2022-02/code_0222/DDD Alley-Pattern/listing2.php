trait AlertTrait
{
    use LocatorAwareTrait;
    protected AlertLevelsTable $alertLevelsTable;
    protected AlertMessagesTable $alertMessagesTable;
    protected AlertPeriodsTable $alertPeriodsTable;
    protected AlertTypesTable $alertTypesTable;
    protected AlertsTable $alertsTable;
    protected function loadAlertLevelsTable(): void
    {
        $this->alertLevelsTable = $this->alertLevelsTable();
    }
    protected function alertLevelsTable()
        : AlertLevelsTable
    {
        /** @noinspection PhpUnnecessaryLocalVariableInspection */
        /** @var AlertLevelsTable $table */
        $table = $this->getTableLocator()->get('AlertLevels');
        return $table;
    }
}