<?php
session_start();

class User {
    private $userId;
    private $userName;
    private $userEmail;
    private $userRole;

    public function __construct() {
        // Check if user is already logged in
        if (isset($_SESSION['user_id'])) {
            $this->userId = $_SESSION['user_id'];
            $this->userName = $_SESSION['user_name'];
            $this->userEmail = $_SESSION['user_email'];
            $this->userRole = $_SESSION['user_role'];
        }
    }

    public function login($id, $name, $email, $role) {
    // Store user data in session and class properties
        $_SESSION['user_id'] = $id;
        $_SESSION['user_name'] = $name;
        $_SESSION['user_email'] = $email;
        $_SESSION['user_role'] = $role;

        $this->userId = $id;
        $this->userName = $name;
        $this->userEmail = $email;
        $this->userRole = $role;
    }

    public function logout() {
        // Clear session data and class properties
        unset($_SESSION['user_id']);
        unset($_SESSION['user_name']);
        unset($_SESSION['user_email']);
        unset($_SESSION['user_role']);

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

// Example usage:
$user = new User();

// Log in a user
$user->login(1, 'Chris', 'chris@example.com', 'admin');

// Access user data
echo 'User ID: ' . $user->getUserId() . '<br>';
echo 'User Name: ' . $user->getUserName() . '<br>';
echo 'User Email: ' . $user->getUserEmail() . '<br>';
echo 'User Role: ' . $user->getUserRole() . '<br>';

// Log out the user
$user->logout();

// After logout, accessing user data should return null
echo 'User ID after logout: ' .
        $user->getUserId() . '<br>';
?>