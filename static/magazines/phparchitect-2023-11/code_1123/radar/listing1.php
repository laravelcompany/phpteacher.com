use LaravelWebauthn\Actions\AttemptToAuthenticate;
use LaravelWebauthn\Actions\EnsureLoginIsNotThrottled;
use LaravelWeb...\Actions\PrepareAuthenticatedSession;
use LaravelWebauthn\Services\Webauthn;
use Illuminate\Http\Request;

Webauthn::authenticateThrough(
    function (Request $request) {
        return array_filter([
            config('webauthn.limiters.login')!== null ?
                null :
                EnsureLoginIsNotThrottled::class,
            AttemptToAuthenticate::class,
            PrepareAuthenticatedSession::class,
        ]);
    }
);