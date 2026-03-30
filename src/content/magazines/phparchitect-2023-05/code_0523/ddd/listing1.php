class CaptureFlow
{
    public function captureFlow(array $post): string
    {
        $flow = RegistrationWorkflow::captureFlow();
        $_SESSION['registration_flow'] = $flow;
        // Save form POST data for recovery after
        // round trip to Stripe
        $_SESSION['stripe_post'] = $post;
        return $flow;
    }
}