<?php
/**
 * Password Hash
 * By Tom Moore (@mooseypx)
 */
// Disallow direct access to this file for security reasons
defined ('IN_MYBB') or die('Direct initialization of this file is not allowed.');

global $lang;
if (defined('IN_ADMINCP') && stripos($lang->language, 'admin') !== false) {
	global $lang;
	$lang->load('config_plugins_psswrdhsh');
}

/**
 * Global Functions
 */
function psswrdhsh_info()
{
	return [
		'name' => 'Password Hashing',
		'description' => "Changes MyBB's default password hashing method.",
		'website' => '',
		'author' => 'Tom Moore',
		'authorsite' => 'https://twitter.com/mooseypx',
		'version' => '1.0',
		'guid'  => '',
		'compatibility' => '18*'
	];
}

/**
 * Install / Uninstall Functions
 * When installed, this plugin doesn't automatically activate
 */
function psswrdhsh_is_installed()
{
	global $db, $settings;

	if (isset($settings['psswrd_cost'])) {
		return true;
	}

	if ($db->field_exists('passwordhash', 'users')) {
		return true;
	}

	return false;
}

function psswrdhsh_install()
{
	global $db, $lang;

	// Dependencies
	// PluginLibrary
	if (!file_exists(PLUGINLIBRARY)) {
		flash_message($lang->error_pwh_pl_missing, 'error');
		admin_redirect('index.php?module=config-plugins');
	}

	global $PL;
	$PL or require_once PLUGINLIBRARY;

	if ($PL->version < 12) {
        flash_message($lang->error_pwh_pl_old, 'error');
        admin_redirect('index.php?module=config-plugins');
    }

	// PHP
	if (version_compare(PHP_VERSION, '5.5.0', '<')) {
		flash_message($lang->sprintf($lang->error_pwh_php_old, PHP_VERSION), 'error');
		admin_redirect('index.php?module=config-plugins');
	}

	// MySQL?
	if ($db->type != 'mysql' && $db->type != 'mysqli') {
		flash_message($lang->error_pwh_db_type, 'error');
		admin_redirect('index.php?module=config-plugins');
	}

	// Uninstall
	psswrdhsh_uninstall();

	// Settings
	// Figure out the optimal cost for this server
	// From: http://php.net/manual/en/function.password-hash.php
	// Password used is 8 chars + 2 numbers
	$target = 0.05;
	$cost = 8;

	do {
		++$cost;
		$start = microtime(true);
		password_hash('z2d4BYAzsB', PASSWORD_BCRYPT, ['cost' => $cost]);
		$end = microtime(true);
	} while (($end - $start) < $target);

	$settings = [
		[
			'name' => 'psswrd_cost',
			'title' => 'Password Encryption Cost',
			'description' => 'The algorithmic cost that should be used when encrypting passwords. <b>This has been automatically set for the server your forum is running on for optimal performance</b>.<br />Only alter this if you know what it does. No, really. Leave this alone.',
			'optionscode' => 'numeric',
			'value' => (int)$cost,
			'disporder' => 7, // Should appear after the max password length
			'gid' => 9, // member
			'isdefault' => 0
		]
	];

	$db->insert_query_multiple('settings', $settings);
	rebuild_settings();

	// DB changes
	$db->add_column('users', 'passwordhash', "VARCHAR(72) NOT NULL DEFAULT '' AFTER username");
}

function psswrdhsh_uninstall()
{
	global $db;

	// Settings
	$db->delete_query('settings', "name = 'psswrd_cost'");
	rebuild_settings();

	// DB Changes
	if ($db->field_exists('passwordhash', 'users')) {
		$db->drop_column('users', 'passwordhash');
	}
}

/**
 * Activate / Deactivate functions
 * These should mostly just modify core files
 */
function psswrdhsh_activate()
{
	global $lang, $mybb;

	if (psswrdhsh_core_edits('activate') === false) {
		psswrdhsh_uninstall();

		flash_message($lang->error_pwh_activate, 'error');
		admin_redirect('index.php?module=config-plugins');
	}
}

function psswrdhsh_deactivate()
{
	global $lang, $mybb;
	$PL or require_once PLUGINLIBRARY;

	if (psswrdhsh_core_edits('deactivate') === false) {
		flash_message($lang->error_pwh_deactivate, 'error');
		admin_redirect('index.php?module=config-plugins');
	}
}

/**
 * Supporting Functions
 */
function psswrdhsh_core_edits($action)
{
	global $mybb, $PL;
	$PL or require_once PLUGINLIBRARY;

	$results = [];
	if ($action == 'activate') {
		$results[] = $PL->edit_core('psswrdhsh', 'inc/functions_user.php', [
			[
				'search' => 'function validate_password_from_uid',
				'replace' => 'function old_validate_password_from_uid($uid, $password, $user = array())',
				'before' => 'require_once MYBB_ROOT.\'inc/plugins/psswrdhsh/functions_user.php\';'
			]
		], true) ?: 0;
	} else if ($action == 'deactivate') {
		$results[] = $PL->edit_core('psswrdhsh', 'inc/functions_user.php', [], true) ?: 0;
	}

	// Return false if we have failed to apply edits
	if (in_array(0, $results)) {
		return false;
	} else {
		return true;
	}
}
