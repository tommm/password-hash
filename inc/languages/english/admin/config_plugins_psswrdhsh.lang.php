<?php
/**
 * Password Hash
 * By Tom Moore (@mooseypx)
 */
// Disallow direct access to this file for security reasons
defined ('IN_MYBB') or die('Direct initialization of this file is not allowed.');

$l['error_pwh_pl_missing'] = 'Password Hash could not be installed because PluginLibrary is missing.';
$l['error_pwh_pl_old'] = 'The selected plugin could not be installed because PluginLibrary is too old.';
$l['error_pwh_php_old'] = 'Password Hash could not be installed because it requires PHP 5.5.0 or greater ({1} is currently used).';
$l['error_pwh_db_type'] = 'Password Hash could not be installed because your database type is not supported.';
$l['error_pwh_activate'] = 'Advanced Password Hash could not be activated because core files could not be modified. Please check the documentation CHMOD permissions and try again.';
$l['error_pwh_uninstall'] = 'Advanced Password Hash cannot not be deactivated. For usage instructions please check the documentation.';
$l['error_pwh_deactivate'] = 'Advanced Password Hash could not be uninstalled because core files could not be modified. Please check the documentation and CHMOD permissions and try again.';
