<?php
/**
 * ReadyLaunch - Members Registration template.
 *
 * This template handles the member registration form for blogs and member signups.
 *
 * @package BuddyBoss\Template
 * @subpackage BBoss_Nouveau\ReadyLaunch
 * @since BuddyBoss 2.9.00
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

bboss_nouveau_signup_hook( 'before', 'page' );
$bp_get_current_signup_step = bboss_get_current_signup_step();
?>
<div id="register-page" class="page register-page">
	<?php
	bboss_nouveau_template_notices();
	bboss_nouveau_user_feedback( $bp_get_current_signup_step );
	?>
	<form action="" name="signup_form" id="signup-form" class="standard-form signup-form clearfix" method="post" enctype="multipart/form-data">
		<div class="layout-wrap">
			<?php
			if ( 'request-details' === $bp_get_current_signup_step ) {

				bboss_nouveau_signup_hook( 'before', 'account_details' );
				?>

				<div class="register-section default-profile" id="basic-details-section">
					<?php
					/***** Basic Account Details ******/
					bboss_nouveau_signup_form();
					?>
				</div><!-- #basic-details-section -->

				<?php
				bboss_nouveau_signup_hook( 'after', 'account_details' );
				/***** Extra Profile Details */
				if ( bboss_is_active( 'xprofile' ) && bboss_nouveau_base_account_has_xprofile() ) {
					bboss_nouveau_signup_hook( 'before', 'signup_profile' );
					?>
					<div class="register-section extended-profile" id="profile-details-section">

						<?php
						/* Use the profile field loop to render input fields for the 'base' profile field group */
						while ( bboss_profile_groups() ) :
							bboss_the_profile_group();

							while ( bboss_profile_fields() ) :
								bboss_the_profile_field();

								if ( function_exists( 'bboss_member_type_enable_disable' ) && false === bboss_member_type_enable_disable() ) {
									if ( function_exists( 'bboss_get_xprofile_member_type_field_id' ) && bboss_get_the_profile_field_id() === bboss_get_xprofile_member_type_field_id() ) {
										continue;
									}
								}
								?>

								<div
								<?php
								bboss_field_css_class( 'editfield' );
								bboss_field_data_attribute();
								?>
								>
									<fieldset>
										<?php
										$field_type = bboss_xprofile_create_field_type( bboss_get_the_profile_field_type() );
										$field_type->edit_field_html();
										?>
									</fieldset>
								</div>

								<?php
							endwhile;
							?>
							<input type="hidden" name="signup_profile_field_ids" id="signup_profile_field_ids" value="<?php bboss_the_profile_field_ids(); ?>" />
							<?php
						endwhile;
						bboss_nouveau_signup_hook( '', 'signup_profile' );
						?>
					</div><!-- #profile-details-section -->
					<?php
					bboss_nouveau_signup_hook( 'after', 'signup_profile' );
				}

				if ( bboss_get_blog_signup_allowed() ) {

					bboss_nouveau_signup_hook( 'before', 'blog_details' );
					/***** Blog Creation Details */
					?>
					<div class="register-section blog-details" id="blog-details-section">
						<h2><?php esc_html_e( 'Site Details', 'buddyboss-platform' ); ?></h2>
						<p>
							<input type="checkbox" name="signup_with_blog" id="signup_with_blog" class="bs-styled-checkbox" value="1" <?php checked( (int) bboss_get_signup_with_blog_value(), 1 ); ?> />
							<label for="signup_with_blog"><?php esc_html_e( "Yes, I'd like to create a new site", 'buddyboss-platform' ); ?></label>
						</p>
						<div id="blog-details"
						<?php
						if ( (int) bboss_get_signup_with_blog_value() ) :
							?>
							class="show"<?php endif; ?>>
							<?php bboss_nouveau_signup_form( 'blog_details' ); ?>
						</div>
					</div><!-- #blog-details-section -->
					<?php
					bboss_nouveau_signup_hook( 'after', 'blog_details' );
				}
			}
			?>
		</div><!-- //.layout-wrap -->
		<?php
		if ( 'request-details' === $bp_get_current_signup_step ) {
			bboss_nouveau_signup_terms_privacy();
			bboss_nouveau_submit_button( 'register' );
		} // request-details signup step.

		bboss_nouveau_signup_hook( 'custom', 'steps' );
		?>
	</form>
</div>
<?php bboss_nouveau_signup_hook( 'after', 'page' ); ?>
