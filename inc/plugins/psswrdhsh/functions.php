<?php
/**
 * Password Hash
 * By Tom Moore (@mooseypx)
 */
// Disallow direct access to this file for security reasons
defined ('IN_MYBB') or die('Direct initialization of this file is not allowed.');

/**
 * This function validates the password_hash login
 */
function psswrdhsh_login_validate($handler)
{
	global $db, $settings;

	if (empty($handler->login_data['uid'])) {
		return;
	}

	$uid = (int)$handler->login_data['uid'];
	$query = $db->simple_select('users', 'passwordhash', "uid = '{$uid}'");
	$hash = $db->fetch_field($query, 'passwordhash');

	if (!empty($hash)) {
		// Only verify passwordhash
		if (password_verify($handler->data['password'], $hash) === true) {
			if (!isset($handler->errors['regimageinvalid']) && !isset($handler->errors['regimagerequired'])) {
				// Only allow this user through if they don't have to put in the CAPTCHA
				$handler->errors = [];
			}
		} else {
			$handler->invalid_combination(true);
		}
	} else if (count($handler->get_errors()) == 0 && PSSWRD_CONV == true) {
		// This user doesn't have a hashed password
		// If allowed, just covert their password on the fly
		$new_password_hash = password_hash(
			$handler->data['password'],
			PASSWORD_BCRYPT,
			['cost' => $settings['psswrd_cost']]
		);

		// Set a completely random password
		$new_password = salt_password(md5(random_str(10, true)), $handler->login_data['salt']);

		// Update user
		$update_array = [
			'passwordhash' => $db->escape_string($new_password_hash),
			'password' => $db->escape_string($new_password)
		];

		$db->update_query('users', $update_array, "uid = '{$uid}'");
	}
}

/**
 * Place our passwordhash field into the user handler
 */
function psswrdhsh_insert_update_user($handler)
{
	global $db, $mybb, $plugins, $settings;

	// This is the actual place where we put data into the database
	// It's already been validated
	$user = &$handler->data;

	if($handler->method == 'insert') {
		// Insert
		$data = &$handler->user_insert_data;
		$plugins->add_hook('datahandler_user_insert_end', 'psswrdhsh_insert_user_end');
	} else {
		// Update
		$data = &$handler->user_update_data;
	}

	// Setup new password
	$new_password_hash = password_hash(
		$handler->data['password'],
		PASSWORD_BCRYPT,
		['cost' => $settings['psswrd_cost']]
	);

	$data['passwordhash'] = $db->escape_string($new_password_hash);
	$user['hashedpassword'] = $new_password_hash;

	// Set a completely random password which will force
	// the user to 'forget password' if psswrdhsh is disabled
	$new_password = random_str();
	$user['md5password'] = md5($new_password);
	$user['saltedpw'] = $data['password'] = salt_password($user['md5password'], $user['salt']);
}

function psswrdhsh_insert_user_end($handler)
{
	// Just gives the handler the proper password to use
	$handler->return_values['password'] = $handler->data['hashedpassword'];
}