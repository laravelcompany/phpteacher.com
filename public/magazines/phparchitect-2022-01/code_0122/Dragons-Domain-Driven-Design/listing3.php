<?php

declare(strict_types=1);

namespace App\BoundedContexts\AMS\Factory\CreateAccount;

class CreateAccountFactory
{
    public static function addMedicalInfo(
        RegistrationController $controller,
        ServerRequest $request
    ): AddMedicalInfo {
        $reporter = new ReportError($controller);
        $repository = new RAddMedicalInfo($reporter, $request);
        return new AddMedicalInfo($reporter, $controller, $request, $repository);
    }

    public static function addGuardianContacts(
        RegistrationController $controller,
        ServerRequest $request
    ): AddGuardianContacts {
        $reporter = new ReportError($controller);
        $repository = new RAddGuardianContacts($reporter, $request);
        return new AddGuardianContacts($reporter, $controller, $request, $repository);
    }
}