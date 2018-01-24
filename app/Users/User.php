<?php

namespace App\Users;

use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;

use App\Miners\Miner;
use App\Payouts\Payout;

class User extends Authenticatable
{
	use Notifiable;
	use \App\Support\HasUuid;

	/**
	 * The attributes that are mass assignable.
	 *
	 * @var array
	 */
	protected $fillable = [
		'nick', 'email', 'password',
	];

	/**
	 * The attributes that should be hidden for arrays.
	 *
	 * @var array
	 */
	protected $hidden = [
		'password', 'remember_token',
	];

	/* relations */
	public function miners()
	{
		return $this->hasMany(Miner::class);
	}

	/* attributes */
	public function getDisplayNickAttribute()
	{
		return $this->nick;
	}

	/* methods */
	public function getPayouts()
	{
		$addresses = $this->miners->pluck('address');
		return Payout::whereIn('recipient', $addresses ?: ['none'])->orderBy('id', 'asc')->paginate(500);
	}

	public function getPayoutsSum()
	{
		$addresses = $this->miners->pluck('address');
		return Payout::selectRaw('sum(amount) sum')->whereIn('recipient', $addresses ?: ['none'])->pluck('sum')->first();
	}

	public function getPayoutsCount()
	{
		$addresses = $this->miners->pluck('address');
		return Payout::whereIn('recipient', $addresses ?: ['none'])->count();
	}

	public function getDailyPayouts()
	{
		$addresses = $this->miners->pluck('address');
		return Payout::selectRaw('sum(amount) total, DATE_FORMAT(made_at, "%Y-%m-%d") date')->whereIn('recipient', $addresses ?: ['none'])->groupBy('date')->get();
	}

	public function exportPayoutsToCsv($filename)
	{
		$addresses = $this->miners->pluck('address');
		$in_clause = array_fill(0, count($addresses), '?');

		return \DB::statement('SELECT made_at, sender, recipient, amount FROM payouts WHERE recipient IN (' . implode(', ', $in_clause) . ')
			INTO OUTFILE ' . \DB::getPdo()->quote($filename) . ' FIELDS TERMINATED BY "," ENCLOSED BY \'"\' LINES TERMINATED BY "\n"', $addresses->toArray());
	}

	public function isActive()
	{
		return $this->active;
	}

	public function isAdministrator()
	{
		return $this->administrator;
	}
}
