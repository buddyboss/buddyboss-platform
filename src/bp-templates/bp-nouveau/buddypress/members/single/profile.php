<?php
/**
 * The template for users profile
 *
 * This template can be overridden by copying it to yourtheme/buddypress/members/single/profile.php.
 *
 * @since   BuddyPress 3.0.0
 * @version 1.0.0
 */
?>

<?php bp_get_template_part( 'members/single/parts/item-subnav' ); ?>

<?php bp_nouveau_member_hook( 'before', 'profile_content' ); ?>

<div class="profile <?php echo bp_current_action(); ?>">

<?php
switch ( bp_current_action() ) :

	// Edit
	case 'edit':
		bp_get_template_part( 'members/single/profile/edit' );
		break;

	// Change Avatar
	case 'change-avatar':
		bp_get_template_part( 'members/single/profile/change-avatar' );
		break;

	// Change Cover Photo
	case 'change-cover-image':
		bp_get_template_part( 'members/single/profile/change-cover-image' );
		break;

	// Compose
	case 'public':
		// Display XProfile
		if ( bp_is_active( 'xprofile' ) ) {
			bp_get_template_part( 'members/single/profile/profile-loop' );

		// Display WordPress profile (fallback)
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
