class RoleBasedLookup
{
    use AlertTrait;
    use ParticipantTrait;
    use SeasonTrait;
    private static array $instances = [];
    private ?League $league = null;
    private ?Participant $participant = null;
    private ?Season $season = null;
    private ?SeasonFee $seasonFee = null;
    private ?SeasonParticipantConfirmation
        $seasonParticipantConfirmation = null;
    private ?SeasonSchedule $seasonSchedule = null;
    private ?Team $team = null;
    private ?TeamSeasonProfile $teamSeasonProfile = null;
    #[Immutable(Immutable::CONSTRUCTOR_WRITE_SCOPE)]
    private UserRole $userRole;
    private function __construct(UserRole $userRole)
    {
        $this->userRole = $userRole;
        $this->loadModels();
    }
    private function loadModels(): void
    {
        $this->loadAlertMessagesTable();
        $this->loadAlertsTable();
        $this->loadLeaguesTable();
        $this->loadParticipantsTable();
        $this->loadSeasonFeesTable();
        $this->loadSeasonParticipantConfirmationsTable();
        $this->loadSeasonSchedulesTable();
        $this->loadSeasonsTable();
        $this->loadTeamSeasonProfilesTable();
        $this->loadTeamsTable();
    }
    public static function getInstance(UserRole $userRole)
        : RoleBasedLookup
    {
        $unique = implode(':',
                          [$userRole->user_id,
                          $userRole->role_id,
                          $userRole->team_id]);
        if (!array_key_exists($unique, self::$instances)) {
            self::$instances[$unique] = new self($userRole);
        }
        return self::$instances[$unique];
    }
}