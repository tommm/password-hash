<?php
/**
 * Password Hash
 * By Tom Moore (@mooseypx)
 */
// Disallow direct access to this file for security reasons
defined ('IN_MYBB') or die('Direct initialization of this file is not allowed.');

/**
 * This function replaces the old v_p_f_u function in ./inc/functions_user.php
 */
function validate_password_from_uid($uid, $password, $user = [])
{
	global $db, $mybb;

	if (isset($mybb->user['uid']) && $mybb->user['uid'] == $uid) {
		$user = $mybb->user;
	}

	if (empty($user)) {
		$uid = (int)$uid;

		$query = $db->simple_select('users', 'uid, username, passwordhash, password, salt, loginkey, usergroup', "uid = '{$uid}'");
		$user = $db->fetch_array($query);
	}

	if (empty($user['passwordhash'])) {
		// If passwordhash is empty, this user hasn't had their password converted
		// Check to see if their password is right
		return old_validate_password_from_uid($uid, $password, $user);
	} else {
		// Password has been converted, lets make sure it's right
		 if (password_verify($password, $user['passwordhash']) === true) {
			 return $user;
		 }

		return false;
	}
}
