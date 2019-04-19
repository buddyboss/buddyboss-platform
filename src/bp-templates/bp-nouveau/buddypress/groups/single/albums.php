<?php
/**
 * BuddyBoss - Groups Album
 *
 * @since BuddyBoss 1.0.0
 */
?>

<?php

switch ( bp_current_action() ) :

	// Home/Media/Albums
	case 'albums':
		if ( ! bp_is_single_album() )
			bp_get_template_part( 'media/albums' );
		else
			bp_get_template_part( 'media/single-album' );
		break;

	// Any other
	default:
		bp_get_template_part( 'groups/single/plugins' );
		break;
endswitch;
