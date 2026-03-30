private function loadSeason(): void
{
    if (null !== $this->season) {
        return;
    }
    $this->loadLeague();
   if (null !== $this->league) {
        $where = [
            Season::FIELD_LEAGUE_TYPE_ID
                  => $this->league->league_type_id,
            Season::FIELD_IS_ACTIVE => true,
        ];
        $order = [Season::FIELD_ID => 'DESC'];
        /** @var Season $entity */
        $entity = $this->seasonsTable
                       ->find()
                       ->where($where)
                       ->order($order)
                       ->first();
        if ($entity instanceof Season) {
            $this->season = $entity;
            return;
        }
    }
    $this->reportMissingRecord('season');
}