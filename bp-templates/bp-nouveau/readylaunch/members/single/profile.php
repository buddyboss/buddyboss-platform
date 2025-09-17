<?php
/**
 * ReadyLaunch - Member Profile template.
 *
 * This template handles displaying member profile sections and editing.
 *
 * @package BuddyBoss\Template
 * @subpackage BP_Nouveau\ReadyLaunch
 * @since BuddyBoss 2.9.00
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

bp_nouveau_member_hook( 'before', 'profile_content' );
?>

<div class="profile <?php echo bp_current_action(); ?> bb-rl-profile-panel">
	<?php
	switch ( bp_current_action() ) :

		// Edit.
		case 'edit':
			bp_get_template_part( 'members/single/profile/edit' );
			break;

		// Change Avatar.
		case 'change-avatar':
			bp_get_template_part( 'members/single/profile/change-avatar' );
			break;

		// Compose.
		case 'public':
			// Display XProfile
			if ( bp_is_active( 'xprofile' ) ) {
				bp_get_template_part( 'members/single/profile/profile-loop' );

				// Display WordPress profile (fallback).
			} else {
				bp_get_template_part( 'members/single/profile/profile-wp' );
			}

			break;

		// Any other
		default:
			bp_get_template_part( 'members/single/plugins' );
			break;
	endswitch;
	?>
</div><!-- .profile -->

<?php
bp_nouveau_member_hook( 'after', 'profile_content' );
