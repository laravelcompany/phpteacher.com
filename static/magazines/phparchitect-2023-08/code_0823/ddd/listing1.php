<?php

/** Submitted email address in users table? */
if ($existingUser) {
  /** User have active coach role on active team? */
  $schools =
      $controller->getExistingCoachRoles($UserEmail);
  $hasOtherCoachRoles = !empty($schools);
  /** Fall through to render with "are you sure?" */
} else {
  /** Submitted email address NOT in users table */
  /** Sendgrid validate email? */
  if ($controller->validateEmail($UserEmail)) {
    /** Twilio validate phone? */
    if ($controller->validatePhone($Phone)) {
      /** Create new user */
      $addUserResponse = $controller
                  ->createNewUser($addTeamRequest);
      if ($addUserResponse->isSuccess()) {
        $UserID = $addUserResponse->getUserId();
      } else {
        $message = $addUserResponse->getErrorMessage();
      }
    } else {
      /** Twilio did NOT validate phone */
      $message = 'Invalid phone number';
    }
  } else {
    /** Sendgrid did NOT validate email */
    $message = 'Invalid email address';
  }
}