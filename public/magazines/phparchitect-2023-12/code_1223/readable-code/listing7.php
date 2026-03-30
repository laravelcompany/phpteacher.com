
session_start();

// Suppose we want to store user information
$_SESSION['user_id'] = 1;
$_SESSION['user_name'] = 'Chris';
$_SESSION['user_email'] = 'chris@example.com';
$_SESSION['user_role'] = 'admin';

// Later in the code, we may update user data directly
$_SESSION['user_role'] = 'user';

// Retrieve user data directly from $_SESSION
$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];
$user_email = $_SESSION['user_email'];

// Deleting user data when it's no longer needed
unset($_SESSION['user_id']);
unset($_SESSION['user_name']);
unset($_SESSION['user_email']);
unset($_SESSION['user_role']);

// Destroy the entire session when the user logs out
session_destroy();