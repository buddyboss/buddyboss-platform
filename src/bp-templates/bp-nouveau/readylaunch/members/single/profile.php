<?php
/**
 * ReadyLaunch - Member Profile template.
 *
 * This template handles displaying member profile sections and editing.
 *
 * @package BuddyBoss\Template
 * @subpackage BBoss_Nouveau\ReadyLaunch
 * @since BuddyBoss 2.9.00
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

bboss_nouveau_member_hook( 'before', 'profile_content' );
?>

<div class="profile <?php echo esc_attr( bboss_current_action() ); ?> bb-rl-profile-panel">
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
