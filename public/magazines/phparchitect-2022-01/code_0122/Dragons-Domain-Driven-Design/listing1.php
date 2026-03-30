<?php

declare(strict_types=1);

namespace App\BoundedContexts\AMS\ApplicationServices\CreateAccount;

use App\BoundedContexts\AMS\Repository\CreateAccount\RAddMedicalInfo;
use App\BoundedContexts\Infrastructure\ReportError\ReportError;
use App\Controller\RegistrationController;
use App\Model\Entity\Participant;
use App\Model\Entity\User;
use Cake\Http\ServerRequest;
use JetBrains\PhpStorm\Immutable;
use JetBrains\PhpStorm\Pure;

class AddMedicalInfo extends BaseCreateAccount
{
    #[Immutable(Immutable::CONSTRUCTOR_WRITE_SCOPE)]
    private RAddMedicalInfo $repository;

    #[Pure]
    public function __construct(
        ReportError $reporter,
        RegistrationController $controller,
        ServerRequest $request,
        RAddMedicalInfo $repository
    ) {
        parent::__construct($reporter, $controller, $request);
        $this->repository = $repository;
    }

    public function newParticipantEntity(): Participant
    {
        return $this->repository->newParticipantEntity();
    }

    public function loadParticipant(int $id): Participant
    {
        return $this->repository->loadParticipant($id);
    }

    public function processMedicalInfo(Participant $participant, User $user): bool
    {
        return $this->repository->updateParticipantWithMedicalInfo($participant, $user);
    }
}