<?php
/**
 * BuddyBoss - Members Single Profile WP
 *
 * @since BuddyPress 3.0.0
 * @version 3.1.0
 */

bp_nouveau_wp_profile_hooks( 'before' ); ?>

<div class="bp-widget wp-profile">

	<h2 class="screen-heading wp-profile-screen">
		<?php
		if ( bp_is_my_profile() ) {
			esc_html_e( 'My Profile', 'buddyboss' );
		} else {
			printf(
				/* Translators: a member's profile, e.g. "Paul's profile". */
				__( "%s's Profile", 'buddyboss' ),
				bp_get_displayed_user_fullname()
			);
		}
		?>
	</h2>

	<?php if ( bp_nouveau_has_wp_profile_fields() ) : ?>

		<table class="wp-profile-fields">

			<?php
			while ( bp_nouveau_wp_profile_fields() ) :
				bp_nouveau_wp_profile_field();

				// Get the current display settings from BuddyBoss > Settings > Profiles > Display Name Format.
				$current_value = bp_get_option( 'bp-display-name-format' );

				// If First Name selected then do not add last name field.
				if ( 'first_name' === $current_value && bp_get_the_profile_field_id() === bp_xprofile_lastname_field_id() ) {
					if ( function_exists( 'bp_hide_last_name') && false === bp_hide_last_name() ) {
						continue;
					}
					// If Nick Name selected then do not add first & last name field.
				} elseif ( 'nickname' === $current_value && bp_get_the_profile_field_id() === bp_xprofile_lastname_field_id() ) {
					if ( function_exists( 'bp_hide_nickname_last_name') && false === bp_hide_nickname_last_name() ) {
						continue;
					}
				} elseif ( 'nickname' === $current_value && bp_get_the_profile_field_id() === bp_xprofile_firstname_field_id() ) {
					if ( function_exists( 'bp_hide_nickname_first_name') && false === bp_hide_nickname_first_name() ) {
						continue;
					}
				}
			?>

				<tr id="<?php bp_nouveau_wp_profile_field_id(); ?>">
					<td class="label"><?php bp_nouveau_wp_profile_field_label(); ?></td>
					<td class="data"><?php bp_nouveau_wp_profile_field_data(); ?></td>
				</tr>

			<?php endwhile; ?>

		</table>

	<?php else : ?>

		<?php bp_nouveau_user_feedback( 'member-wp-profile-none' ); ?>

	<?php endif; ?>

</div>

<?php
bp_nouveau_wp_profile_hooks( 'after' );

