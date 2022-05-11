<?php
/**
 * Deprecated functions.
 *
 * @deprecated BuddyBoss 2.0.1
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Save group message meta.
 *
 * @since BuddyBoss 1.2.9
 * 
 * @deprecated BuddyBoss 2.0.1 Use bb_messages_save_group_data() instead.
 * 
 * @todo Update deprecated version.
 * 
 * @param $message
 */
function bp_media_messages_save_group_data( &$message ) {	
	_deprecated_function( __FUNCTION__, '2.0.1', 'bb_messages_save_group_data' );
	bb_messages_save_group_data( $message );
}
