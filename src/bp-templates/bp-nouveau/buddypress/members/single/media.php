<?php
/**
 * BuddyBoss - Users Media
 *
 * @since BuddyBoss 1.0.0
 */
?>

<?php if ( bp_is_my_profile() ) : ?>
	<?php bp_get_template_part( 'members/single/parts/item-subnav' ); ?>
<?php endif; ?>

<?php

switch ( bp_current_action() ) :

	// Home/Media
	case 'my-media':
		bp_get_template_part( 'members/single/media/my-media' );
		break;

	// Home/Media/Albums
	case 'albums':
		bp_get_template_part( 'members/single/media/albums' );
		break;

	// Any other
	default:
		bp_get_template_part( 'members/single/plugins' );
		break;
endswitch;
