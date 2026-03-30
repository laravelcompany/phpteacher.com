namespace App\Http\Controllers;

use App\Models\Account;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class AccountController extends Controller
{
	// ...

	public function read(Request $request, int $id)
	{
		if (!Gate::allows('get-account', $id)) {
		  abort(403);
		}

		return Account::findOrFail($id);
	}

	// ...
}