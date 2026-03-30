namespace App\Http\Controllers;

use App\Models\Account;
use Illuminate\Http\Request;

class AccountController extends Controller
{
	public function create(Request $request)
	{
		$account = new Account;

		// ...Populate the model
		$account->save();

		return $account;
	}

	public function read(int $id)
	{
		return Account::findOrFail($id);
	}

	public function update(Request $request, int $id)
	{
		$account = Account::findOrFail($id);

		// ...Populate changes onto the model
		$account->save();

		return $account;
	}

	public function delete(int $id)
	{
		return (Account::findOrFail($id))->delete();
	}
}