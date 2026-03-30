<?php
session_start();

class Session {
    public function __construct() {
        session_start();
    }

    public function destroy() {
        session_destroy();
    }

    public function set($key, $value) {
        $_SESSION[$key] = $value;
    }

    public function get($key) {
        return $_SESSION[$key] ?? null;
    }

    public function remove($key) {
        unset($_SESSION[$key]);
    }
}

class User {
    private $userId;
    private $userName;
    private $userEmail;
    private $userRole;

    public function __construct($session) {
        // Check if user is already logged in
        if ($session->get('user_id')) {
          $this->userId = $session->get('user_id');
          $this->userName = $session->get('user_name');
          $this->userEmail =
                           $session->get('user_email');
          $this->userRole = $session->get('user_role');
        }
    }

    public function login(
        $session, $id, $name, $email, $role
    ) {
    // Store user data in session and class properties
        $session->set('user_id', $id);
        $session->set('user_name', $name);
        $session->set('user_email', $email);
        $session->set('user_role', $role);

        $this->userId = $id;
        $this->userName = $name;
        $this->userEmail = $email;
        $this->userRole = $role;
    }

    public function logout($session) {
        // Clear session data and class properties
        $session->remove('user_id');
        $session->remove('user_name');
        $session->remove('user_email');
        $session->remove('user_role');

        $this->userId = null;
        $this->userName = null;
        $this->userEmail = null;
        $this->userRole = null;
    }

    public function getUserId() {
        return $this->userId;
    }

    public function getUserName() {
        return $this->userName;
    }

    public function getUserEmail() {
        return $this->userEmail;
    }

    public function getUserRole() {
        return $this->userRole;
    }
}

// Create a session instance
$session = new Session();

// Create a user instance with access to the session
$user = new User($session);

// Example usage:
if (!$user->getUserId()) {
    // Log in a user if not already logged in
    $user->login($session, 1, 'Chris',
                    'chris@example.com', 'admin');
}