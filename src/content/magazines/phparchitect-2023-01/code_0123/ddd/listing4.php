<?php
final class ForTeamProfile extends CtgoAuthorize
    implements CLookupRoleTypes
{
    public function authorize(): void
    {
        $this->loginToken = $this->repository
            ->loadLoginToken()
        ;
        $this->team = $this->repository
            ->loadTeamWithLeague()
        ;
        $examiner = new ExamineToken($this->loginToken);
        if ($examiner->isExpired()) {
            $response = new CtgoResponse(403);
            throw new CtgoException(
                $response, 'Login expired', 403
            );
        }
        /* ...additional code here... */
    }
}