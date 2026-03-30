<?php
class TeamsController extends AppController
{
    public function viewHeadCoach(): ?Response
    {
        $this->request->allowMethod('get');
        $ctgoAuthorize =
            CtgoAuthorizeFactory::forTeamProfile(
                $this->request
            );
        try {
            $ctgoAuthorize->authorize();
            $team = $ctgoAuthorize->getTeam();
            $userRole = $this->loadUserRole($team);
        } catch (CtgoException $e) {
            $this->processCtgoException($e);
            return null;
        }

        $results = (new MapTeamStaff())->map($userRole);

        $this->set(compact('results'));
        $this->viewBuilder()
            ->setOption('serialize', 'results')
        ;

        return null;
    }
}