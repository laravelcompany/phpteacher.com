namespace App\Http\Controllers;

use App\Models\Account;
use Illuminate\Http\Request;

class AccountController extends Controller
{
	// ...

	public function read(Request $request, int $id)
	{
		$userId = $request->session()->get('user_id');

		if ($userId !== $id) {
			abort(403);
		}

		return Account::findOrFail($id);
	}

	// ...
}