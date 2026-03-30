<?php

declare(strict_types=1);

namespace App\BoundedContexts\AMS\Repository\CreateAccount;

use App\BoundedContexts\AMS\DomainModel\Interfaces\Constants\CProfile;
use App\BoundedContexts\Infrastructure\LoadTableModels\ParticipantTrait;
use App\BoundedContexts\Infrastructure\ReportError\CReportError;
use App\BoundedContexts\Infrastructure\ReportError\ReportError;
use App\BoundedContexts\Infrastructure\ThirdNormal\CCreatedBy;
use App\Model\Entity\Participant;
use App\Model\Entity\User;
use Cake\Database\Exception\DatabaseException;
use Cake\Http\ServerRequest;
use Cake\I18n\FrozenDate;
use Exception;
use JetBrains\PhpStorm\Immutable;

class RAddMedicalInfo implements CCreatedBy, CProfile, CReportError
{
    use ParticipantTrait;

    #[Immutable(Immutable::CONSTRUCTOR_WRITE_SCOPE)]
    private ReportError $reporter;
    #[Immutable(Immutable::CONSTRUCTOR_WRITE_SCOPE)]
    private ServerRequest $request;

    public function __construct(ReportError $reporter, ServerRequest $request)
    {
        $this->reporter = $reporter;
        $this->loadModels();
        $this->request = $request;
    }

    private function loadModels(): void
    {
        $this->loadParticipantsTable();
    }

    public function newParticipantEntity(): Participant
    {
        return $this->participantsTable->newEmptyEntity();
    }

    public function loadParticipant(int $id): Participant
    {
        return $this->participantsTable->get($id);
    }

    public function updateParticipantWithMedicalInfo(Participant $participant, User $user): bool
    {
        $connection = $this->participantsTable->getConnection();
        try {
            $connection->transactional(function () use ($participant, $user) {
                // Refresh participant inside transaction
                $entity = $this->participantsTable->get($participant->id);
                $entity->is_active = true;
                $entity->modified_by = $user->id;
                $data = $this->request->getData();
                $consent = $data[Participant::FIELD_MEDICAL_TREATMENT_CONSENT] ?? false;
                if ($consent) {
                    $entity->medical_consent_date = new FrozenDate();
                }
                $entity = $this->participantsTable->patchEntity($entity, $data);
                if ($entity->hasErrors()) {
                    $this->reporter->processPatchErrors($entity->getErrors());
                    throw new DatabaseException('Participant patch errors');
                }
                $this->participantsTable->saveOrFail($entity);
            });
        } catch (Exception $e) {
            $message = 'Could not update participant with medical info';
            $this->reporter->flashError($message);
            $message .= ': ' . $e->getMessage();
            $detail = [
                'participant' => $participant->toArray(),
                'user' => $user->toArray(),
                'backtrace' => $e->getTrace(),
            ];
            $this->reporter->logError($message, self::ERROR_TYPE_LOGGABLE, $detail);
            $this->reporter->flush();
            return false;
        }
        return true;
    }
}