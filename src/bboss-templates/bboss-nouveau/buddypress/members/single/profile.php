<?php
/**
 * The template for users profile
 *
 * This template can be overridden by copying it to yourtheme/buddypress/members/single/profile.php.
 *
 * @since   BuddyPress 3.0.0
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

bboss_get_template_part( 'members/single/parts/item-subnav' );
bboss_nouveau_member_hook( 'before', 'profile_content' );
?>

<div class="profile <?php echo esc_attr( bboss_current_action() ); ?>">
	<?php
	switch ( bboss_current_action() ) :

		// Edit.
		case 'edit':
			bboss_get_template_part( 'members/single/profile/edit' );
			break;

		// Change Avatar.
		case 'change-avatar':
			bboss_get_template_part( 'members/single/profile/change-avatar' );
			break;

		// Change Cover Photo.
		case 'change-cover-image':
			bboss_get_template_part( 'members/single/profile/change-cover-image' );
			break;

		// Compose.
		case 'public':
			// Display XProfile
			if ( bboss_is_active( 'xprofile' ) ) {
				bboss_get_template_part( 'members/single/profile/profile-loop' );

				// Display WordPress profile (fallback).
			} else {
				bboss_get_template_part( 'members/single/profile/profile-wp' );
			}

			break;

		// Any other
		default:
			bboss_get_template_part( 'members/single/plugins' );
			break;
	endswitch;
	?>
</div><!-- .profile -->

<?php
bboss_nouveau_member_hook( 'after', 'profile_content' );
