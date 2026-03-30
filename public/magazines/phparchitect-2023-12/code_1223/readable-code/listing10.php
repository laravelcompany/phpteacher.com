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

class Authentication {
    public function login(
        $session, $id, $name, $email, $role
    ) {
        // Store user data in session
        $session->set('user_id', $id);
        $session->set('user_name', $name);
        $session->set('user_email', $email);
        $session->set('user_role', $role);
    }

    public function logout($session) {
        // Clear session data
        $session->remove('user_id');
        $session->remove('user_name');
        $session->remove('user_email');
        $session->remove('user_role');
    }
}

// Create a session instance
$session = new Session();

// Create an authentication instance
$authentication = new Authentication();

// Create a user instance with access to the session
$user = new User($session);

// Example usage:
if (!$user->getUserId()) {
    // Log in a user if not already logged in
    $authentication->login($session, 1, 'Chris',
                    'chris@example.com', 'admin');
}

// Access user data
echo 'User ID: ' . $user->getUserId() . '<br>';
echo 'User Name: ' . $user->getUserName() . '<br>';
echo 'User Email: ' . $user->getUserEmail() . '<br>';
echo 'User Role: ' . $user->getUserRole() . '<br>';

// Log out the user
$authentication->logout($session);

// After logout, accessing user data should return null
echo 'User ID after logout: ' .
                $user->getUserId() . '<br>';

// Destroy the session
$session->destroy();
?>