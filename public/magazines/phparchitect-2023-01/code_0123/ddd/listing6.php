<?php

class UpdateTeamsController extends AppController
{
  public function updateSchool(): ?Response
  {
    $this->request->allowMethod('patch');
    $ctgoAuthorize =
      CtgoAuthorizeFactory::forTeamProfileUpdate(
        $this->request
      );
    try {
      $ctgoAuthorize->authorize();
    } catch (CtgoException $e) {
      $this->processCtgoException($e);
      return null;
    }

    try {
      $this->Teams->getConnection()
        ->transactional(function ()
        use ($ctgoAuthorize) {
          // for modified_by
          $loginToken = $ctgoAuthorize->getLoginToken();
          // Refresh team entity inside transaction
          $team = $this->Teams->get(
            $ctgoAuthorize->getTeam()->id
          );
          $team->modified_by = $loginToken->user_id;

          $value = $this->request
                       ->getData('athletic_director');
          if (
            null !== $value &&
            (strlen($value) <= 250)
          ) {
            $team->athletic_director_name = $value;
          }

          $this->Teams->saveOrFail($team);
        });
      // Other "eventually consistent" items here
    } catch (CtgoException $e) {
      $this->processCtgoException($e);
      return null;
    } catch (Exception) {
      return $this->response->withStatus(400);
    }

    return $this->response->withStatus(204);
  }
}