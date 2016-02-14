<?php
/**
 * Password Hash
 * By Tom Moore (@mooseypx)
 */
// Disallow direct access to this file for security reasons
defined ('IN_MYBB') or die('Direct initialization of this file is not allowed.');

/**
 * Definitions & Dependencies
 */
defined ('PSSWRD_CONV') or define('PSSWRD_CONV', true);
defined ('PLUGINLIBRARY') or define('PLUGINLIBRARY', MYBB_ROOT.'inc/plugins/pluginlibrary.php');

/**
 * Plugin Hooks for DataHandlers
 */
$functions = MYBB_ROOT.'inc/plugins/psswrdhsh/functions.php';

$plugins->add_hook('datahandler_user_insert', 'psswrdhsh_insert_update_user', -999, $functions);
$plugins->add_hook('datahandler_user_update', 'psswrdhsh_insert_update_user', -999, $functions);
$plugins->add_hook('datahandler_login_validate_end', 'psswrdhsh_login_validate', -999, $functions);

/**
 * Start ACP
 */
if (defined('IN_ADMINCP')) {
	require_once MYBB_ROOT.'inc/plugins/psswrdhsh/admin.php';
	return;
}

/**
 * The only point where psswrdhsh interacts with the front end is if the user needs to change their password.
 * If PSSWRD_CONV is false, say to the user that their password has expired and ask them to reset it.
 * If PSSWRD_CONV is true user passwords are converted on-the-fly.
 */
if (PSSWRD_CONV === false) {
	$plugins->add_hook('global_end', 'psswrdhsh_expire_password');
}

function psswrdhsh_expire_password()
{
	global $lang, $mybb;
	if (!empty($mybb->user['passwordhash']) || !$mybb->user['uid']) {
		return;
	}

	if (THIS_SCRIPT != 'usercp.php' && !in_array($mybb->input['action'], ['password', 'do_password'])) {
		$string = $lang->psswrdhsh_expire ?: 'Your password has expired and you must change it.<br />Please wait while we transfer you.';
		redirect('usercp.php?action=password', $string);
	}
}

/**
 * This updates passwordhash with the random password generated when a user forgets their password
 */
$plugins->add_hook('member_resetpassword_process', 'psswrdhsh_update_password');

function psswrdhsh_update_password()
{
	global $db, $password, $settings, $user;

	// Update the password hash
	$new_password_hash = password_hash(
		$password,
		PASSWORD_BCRYPT,
		['cost' => $settings['psswrd_cost']]
	);

	$db->update_query('users', ['passwordhash' => $db->escape_string($new_password_hash)], "uid = '{$user['uid']}'");
}
