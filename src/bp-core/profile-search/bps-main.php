<?php
/*
Plugin Name: BP Profile Search
Plugin URI: http://www.dontdream.it/bp-profile-search/
Description: Search your BuddyPress Members Directory.
Version: 4.9.2
Author: Andrea Tarantini
Author URI: http://www.dontdream.it/
Text Domain: bp-profile-search
*/

define ('BPS_VERSION', '4.9.2');

add_action ('admin_notices', 'bps_no_buddypress');
function bps_no_buddypress ()
{
?>
	<div class="notice notice-error is-dismissible">
		<p><?php _e('BP Profile Search requires BuddyPress with the Extended Profiles component enabled.', 'buddyboss'); ?></p>
	</div>
<?php
}

add_action ('bp_include', 'bps_buddypress');
function bps_buddypress ()
{
	if (bp_is_active ('xprofile'))
	{
		remove_action ('admin_notices', 'bps_no_buddypress');
		include 'bps-start.php';
	}
}
