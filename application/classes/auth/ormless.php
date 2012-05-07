<?php defined('SYSPATH') or die('No direct access allowed.');

class Auth_Ormless extends Auth {
	
	private $_roles = false;
	private $_user = false;
	private $_token = false;
	
	/**
	 * Checks if a user is logged (if a session is active)
	 *
	 * @param   mixed    $role Role name string
	 * @return  boolean
	 */
	public function logged_in($role = null) {
		//Get the user from the session
		$user = $this->get_user();

		//Is there a user logged on?
		if ( ! $user) return false;

		//Are we looking at a specific role here?
		if ( ! $role) return true;

		//Lets check the rolls then!
		$this->_getuserroles($user['iduser']);
		if (is_array($role)) {
			//Check that every given rolls is valid for this user
			foreach ($role AS $rol) {
				if ($this->_hasrole($rol) == false) {
					return false;
				}
			}
		}
		
		//Check the roll
		if ($this->_hasrole($role) == false) {
			return false;
		}
		
		return true;
	}
	
	/**
	 * Logs a user in. We have to deal with checking the username and password are valid.
	 * If invalid, we return false.
	 * If valid we have to call $this->complete_login with an array to store in the session then return true 
	 * We also have to deal with what happens if we want to remember this user (save a cookie, add a token record to user_tokens)
	 *
	 * @param   string   username
	 * @param   string   password
	 * @param   boolean  enable autologin
	 * @return  boolean
	 */
	protected function _login($user, $password, $remember) {
		$username = $user;
			
		//Get the user record. If there is no user, end here
		$user = $this->_getuser($username,null);
		if (empty($user)) { return false; }

		
		// Hash the password and check if it matches
		if (is_string($password)) {
			$password = $this->hash($password);
		}
		
		if ($user['password'] !== $password) {
			return false;
		}
			
		$this->_getuserroles($user['iduser']);
		
		//If the user doesn't have the login role end here
		if ( ! $this->_hasrole('login')) { return false; }
		
		// Finish the login
		$this->complete_login($user);

		return true;
	}

	/**
	 * Force a user login, only requiring the iduser
	 * Ignores roles too.
	 * @param int $iduser
	 */
	public function force_login($iduser) {
		$user = $this->_getuser(null,$iduser);
		if (empty($user)) { return false; }
		
		$this->_getuserroles($user['iduser']);
		
		$this->complete_login($user);
		return true;
		
	}
	
	/**
	 * Check if the given password matches the current users password.
	 * @see Kohana_Auth::check_password()
	 * @deprecated
	 */
	public function check_password($password) {
		$user = $this->get_user();

		if ( ! $user)
			return false;

		return ($this->hash($password) === $user['password']);
	}
	
	/**
	 * Get the stored (hashed) password for a username.
	 *
	 * @param   mixed   username string
	 * @return  string
	 * @deprecated
	 */
	public function password($user) {
		if ( ! $this->_user) {
			$user = $this->_getuser($user,null);
		}
		return $user['password'];
	}
	

	/**
	 * Increment the user login count, set the login date.
	 * @param array $user The user record from _getuser
	 * @see Kohana_Auth::complete_login()
	 */
	protected function complete_login($user) {
		$this->_user = $user;
		$query = "	UPDATE
						users
					SET
						logincount = logincount + 1,
						last_login = :now
					WHERE
						iduser = :iduser ";
		
		DB::query(Database::UPDATE, $query)->param(':iduser',$this->_user['iduser'])->param(':now',$this->_user['current_login'])->execute();

		return parent::complete_login($this->_user);
	}
	
	/**
	 * Get the user information from the database. Anything retrieved here will end up in the session to get later.
	 * This is only used once, when the user first logs in.
	 * @param string $username
	 * @param int $iduser
	 * @return array
	 */
	protected function _getuser($username = null,$iduser = null) {
		$result = array();
		if (is_null($iduser)) {
			$result = Arr::flatten(DB::select()->from('users')->where('username','=',$username)->execute()->as_array());
		} else {
			$result = Arr::flatten(DB::select()->from('users')->where('iduser','=',$iduser)->execute()->as_array());
		}
		if (! empty($result) ) {
			$result['current_login'] = time();
		}
		return $result;
	}
	
	/**
	 * Log out the user
	 * @see Kohana_Auth::logout()
	 */
	public function logout($destroy = true, $logout_all = false) {

		if ($token = Cookie::get('authautologin')) {
			
			// Delete the autologin cookie to prevent re-login
			Cookie::delete('authautologin');

			// Clear the autologin token from the database
			DB::delete('user_tokens')->where('token','=',$token)->execute();
			
			//Delete all the tokens for the user if we are supposed to.
			//This is so it removes the users ability to login from any machine they have a cookie on, not just the one they are using right now.
			if ($logout_all) {
				DB::delete('user_tokens')->where('iduser', '=', $token->iduser)->execute();
			}
		}

		return parent::logout($destroy);
	}
	
	/**
	 * Get the roles for a given user
	 * @param int $iduser
	 */
	protected function _getuserroles($iduser) {
		$query = "	SELECT 
						roles_users.idrole,
						roles.name
					FROM
						roles_users
						LEFT JOIN roles ON (roles.idrole = roles_users.idrole)
					WHERE
						roles_users.iduser = :iduser ";
		$this->_roles = DB::query(Database::SELECT, $query)->param(':iduser',$iduser)->execute()->as_array();
		return $this->_roles;
	}
	
	/**
	 * Check for a given rollname. This is called after the users current
	 * roles are stored in the singleton, so this refers to the current user.
	 * @param string $rolename
	 */
	protected function _hasrole($rolename) {
		$found = false;
		foreach ($this->_roles AS $role) {
			if ($rolename == $role['name']) { $found = true; }
		}
		return $found;
	}

	/**
	 * Check if there is a cookie on the users machine for us to autologin for them
	 */
	protected function _checkautologin() {
		if ($token = Cookie::get('authautologin')) {
			
			$token = $this->_gettoken($token);
			if (! empty($token)) {
				
				if ($token->user_agent === sha1(Request::$user_agent)) {
					//Cookie and token are valid. We are GO for auto login
					
					//Delete old tokens and cookies, make new ones
					return true;
				}
			}
		}
		return false;
	}
	
	/**
	 * Get the user based on their token (which comes from the autologin cookie)
	 * @param string $token
	 */
	protected function _gettoken($token) {
		$result = DB::select()->from('user_tokens')->where('token','=',$token)->execute()->as_array();
		return Arr::flatten($result);
	}
	
	/**
	 * Setup the user for autologin. Creates a token and stores it a cookie.
	 * A token will expire eventually
	 */
	protected function _makeusertoken() {
		$query = "	INSERT INTO
						user_tokens
					SET
						iduser = :iduser,
						expires = :expires,
						user_agent = :useragent,
						token = :token ";
		
		$this->_token = sha1(uniqid(Text::random('alnum', 32), TRUE));
		
		DB::query(Database::INSERT, $query)
			->param(':iduser',$this->_user['iduser'])
			->param(':expires',time() + $this->_config['lifetime'])
			->param(':useragent',sha1(Request::$user_agent))
			->param(':token',$this->_token)
			->execute();

		// Set the autologin cookie
		Cookie::set('authautologin', $this->_token, $this->_config['lifetime']);
	}
	
	/**
	 * Delete any expired tokens
	 */
	protected function _deletexpiredtokens() {
		DB::delete('user_tokens')->where('expires','<',time())->execute();
	}
	

	/**
	 * Get a property of the current user or a default if it does not exist
	 * Returns NULL if no user is currently logged in.
	 *
	 * @return  mixed
	 */
	public function get($field,$default = NULL) {
		$user = $this->_session->get($this->_config['session_key'], $default);
		if ($user == $default) {
			return $default;
		}
		
		if (!isset($user[$field])) {
			return $default;
		}
		
		return $user[$field];
	}
	
	/**
	 * Static functions for user / role creation etc
	 */
	
	
	public static function getUsers() {
		$result = DB::select()->from('users')->execute()->as_array();
		return $result;
	}

	public static function getUser($iduser) {
		$result = DB::select()->from('users')->where('iduser','=',$iduser)->execute()->as_array();
		return Arr::flatten($result);
	}

	/**
	 * Add new user. Will hash the password.
	 * @param array $user
	 */
	public static function addUser($user) {
		
		foreach ($user AS $k => $v) {
			$user[$k] = trim($v);
			if ($k == 'password') {
				$user[$k] = Auth::instance()->hash($v);
			}
		}
		if (!isset($user['created'])) {
			$user['created'] = date(Helper_Constants::MYSQLDATE);
		}
		
		$iduser = DB::insert('users',array_keys($user))->values($user)->execute();
		return $iduser[0];
		
	}

	public static function updateUser($iduser,$user) {
		unset($user['iduser']);
		
		foreach ($user AS $k => $v) {
			$user[$k] = trim($v);
			if ($k == 'password') {
				$user[$k] = Auth::instance()->hash($v);
			}
		}
		
		$result = DB::update('users')->set($user)->where('iduser','=',$iduser)->execute();
		return $result;
	}


	/**
	 * Add a role to a user
	 * @param int $iduser
	 * @param int $idrole
	 */
	public static function addRole($iduser,$idrole) {
		$result = DB::insert('roles_users',array('iduser','idrole'))->values(array('iduser' => $iduser,'idrole' =>$idrole))->execute();
		return $result;
	}

	/**
	 * Used with validation module, returns true if user does NOT exist.
	 * @param string $username
	 */
	public static function CheckUserNotExists($username) {
		//Call CheckUserExists and return the opposite.
		return (self::CheckUserExists($username)) ? false : true;
	}

	/**
	 * Used with validation module, returns true if user exists.
	 * @param string $username
	 */
	public static function CheckUserExists($username) {
		if ($username == '') {
			return false;
		}
		$result = DB::select('username')->from('users')->where('username','=',$username)->execute();

		return ($result->count() >= 1) ? true : false;
	}

	public static function getUserByUsername($username) {
		$result = DB::select('iduser')->from('users')->where('username','=',$username)->execute()->as_array();

		return Arr::flatten($result);
	}

	/**
	 * Update a user record with a new password reset token
	 * @param int $iduser
	 * @return string The token used
	 */
	public static function makePWResetToken($iduser) {
		$data['pwresettoken'] = hash_hmac('sha256',$iduser.time(),time());
		self::updateUser($iduser, $data);
		
		return $data['pwresettoken'];
	}

	/**
	 * Get user record based on token
	 * @param str $token
	 */
	public static function checkPWResetToken($token) {
		$result = DB::select()->from('users')->where('pwresettoken','=',$token)->execute()->as_array();
		return Arr::flatten($result);
	}

	/**
	 * Check if a given user id matches their username
	 * Used in Validation class.
	 * @param int $iduser
	 * @param str $email
	 * @return bool
	 */
	public static function checkUserIDEmail($iduser, $username) {
		$query = DB::select()
		->from('users')
		->where('id', '=', $iduser)
		->and_where('username','=',$username)
		->execute()
		->as_array();
		if (count($query) > 0) {
			return true;
		}
		return false;
	}

}