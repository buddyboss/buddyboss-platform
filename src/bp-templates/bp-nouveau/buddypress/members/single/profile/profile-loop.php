<?php
/**
 * BuddyBoss - Members Profile Loop
 *
 * @since BuddyPress 3.0.0
 * @version 3.1.0
 */

?>

<h2 class="screen-heading view-profile-screen"><?php esc_html_e( 'View Profile', 'buddyboss' ); ?></h2>

<?php bp_nouveau_xprofile_hook( 'before', 'loop_content' ); ?>

<?php if ( bp_has_profile() ) : ?>

	<?php
	while ( bp_profile_groups() ) :
		bp_the_profile_group();
	?>

		<?php if ( bp_profile_group_has_fields() ) : ?>

			<?php bp_nouveau_xprofile_hook( 'before', 'field_content' ); ?>

			<div class="bp-widget <?php bp_the_profile_group_slug(); ?>">

				<h3 class="screen-heading profile-group-title">
					<?php bp_the_profile_group_name(); ?>
				</h3>

				<table class="profile-fields bp-tables-user">

					<?php
					while ( bp_profile_fields() ) :
						bp_the_profile_field();

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

						<?php
						if ( function_exists('bp_member_type_enable_disable' ) && false === bp_member_type_enable_disable() ) {
							if ( function_exists( 'bp_get_xprofile_member_type_field_id') && bp_get_the_profile_field_id() === bp_get_xprofile_member_type_field_id() ) {
								continue;
							}
						}
						?>

                        <?php bp_nouveau_xprofile_hook( 'before', 'field_item' ); ?>
                    
						<?php if ( bp_field_has_data() ) : ?>

							<tr<?php bp_field_css_class(); ?>>

								<td class="label"><?php bp_the_profile_field_name(); ?></td>

								<td class="data"><?php bp_the_profile_field_value(); ?></td>

							</tr>

						<?php endif; ?>

						<?php bp_nouveau_xprofile_hook( '', 'field_item' ); ?>

					<?php endwhile; ?>

                    <?php bp_nouveau_xprofile_hook( 'after', 'field_items' ); ?>
                            
				</table>
			</div>

			<?php bp_nouveau_xprofile_hook( 'after', 'field_content' ); ?>

		<?php endif; ?>

	<?php endwhile; ?>

	<?php bp_nouveau_xprofile_hook( '', 'field_buttons' ); ?>

<?php else: ?>

	<div class="info bp-feedback">
		<span class="bp-icon" aria-hidden="true"></span>
		<p>
			<?php
			if ( bp_is_my_profile() ) {
				esc_html_e( 'You have not yet added details to your profile.', 'buddyboss' );
			} else {
				esc_html_e( 'This member has not yet added details to their profile.', 'buddyboss' );
			}
			?>			
		</p>
	</div>

<?php endif; ?>

<?php
bp_nouveau_xprofile_hook( 'after', 'loop_content' );
