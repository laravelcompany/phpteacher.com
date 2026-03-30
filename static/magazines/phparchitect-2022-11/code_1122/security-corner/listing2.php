use App\Models\User;
use Illuminate\Support\Facades\Gate;
 
/**
 * Register any authentication / authorization services.
 *
 * @return void
 */
public function boot()
{
    $this->registerPolicies();
 
    Gate::define('read-account',
        function (User $user, int $accountId) {
          return $user->id === $accountId;
        }
    );

    Gate::define('update-account',
        function (User $user, int $accountId) {
          return $user->id === $accountId;
        }
    );

    Gate::define('delete-account',
        function (User $user, int $accountId) {
          return $user->id === $accountId;
        }
    );
}