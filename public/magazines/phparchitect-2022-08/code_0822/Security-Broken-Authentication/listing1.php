function checkAuthentication(string $token, array $scopes): void
{
    global $idp;

    try {
        $idp->validateToken($token, $scopes);
    } catch (Exception $error) {
        error_log('Auth failed: ' . $error->getMessage());
    }
}