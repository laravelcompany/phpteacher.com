<?php
public function addTeam(Team $team): bool
{
    if ($this->isFull()) {
        return false;
    }

    if ($team->region === 'UEFA') {
        if ($this->getCountForRegion('UEFA') === 2) {
            // can't place this team here
            return false;
        }
        $this->teams[] = $team;
        return true;
    }

    if (count($this->teams) == 3
        && $this->getCountForRegion('UEFA') === 0) {
        // need to save last spot for a European team
        return false;
    }

    if ($team->region === 'CONCACAF'
        && $this->getCountForRegion('CONMEBOL') > 0) {
        return false;
    }

    if ($team->region === 'CONMEBOL'
        && $this->getCountForRegion('CONCACAF') > 0) {
        return false;
    }

    if ($this->getCountForRegion($team->region) > 0) {
        return false;
    }

    $this->teams[] = $team;
    return true;
}
