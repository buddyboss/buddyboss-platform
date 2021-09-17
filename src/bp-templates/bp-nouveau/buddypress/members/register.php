<?php
/**
 * BuddyBoss - Members/Blogs Registration forms
 *
 * @since BuddyPress 3.0.0
 * @version 3.1.0
 */

?>

	<?php bp_nouveau_signup_hook( 'before', 'page' ); ?>

	<div id="register-page"class="page register-page">

		<?php bp_nouveau_template_notices(); ?>

			<?php bp_nouveau_user_feedback( bp_get_current_signup_step() ); ?>

			<form action="" name="signup_form" id="signup-form" class="standard-form signup-form clearfix" method="post" enctype="multipart/form-data">

			<div class="layout-wrap">

			<?php if ( 'request-details' === bp_get_current_signup_step() ) : ?>

				<?php bp_nouveau_signup_hook( 'before', 'account_details' ); ?>

				<div class="register-section default-profile" id="basic-details-section">

					<?php /***** Basic Account Details ******/ ?>

					<?php

					// Account signup fields.
					$signup_fields         = bp_nouveau_get_signup_fields( 'account_details' );
					$account_signup_fields = array();
					if ( ! empty( $signup_fields ) ) {
						foreach ( $signup_fields as $key => $signup_field ) {
							ob_start();
							$account_signup_fields[ $key ]['id'] = $key;
							bp_nouveau_get_signup_form_html( array( $signup_field ), 'account_details' );
							$account_signup_fields[ $key ]['html'] .= ob_get_clean();
						}
					}

					// Xprofile signup fields.
					$profile_signup_fields    = array();
					$profile_signup_field_ids = '';
					if ( bp_is_active( 'xprofile' ) && bp_nouveau_base_account_has_xprofile() ) {
						while ( bp_profile_groups() ) : bp_the_profile_group();
							while ( bp_profile_fields() ) : bp_the_profile_field();
								if ( function_exists( 'bp_member_type_enable_disable' ) && false === bp_member_type_enable_disable() ) {
									if ( function_exists( 'bp_get_xprofile_member_type_field_id' ) && bp_get_the_profile_field_id() === bp_get_xprofile_member_type_field_id() ) {
										continue;
									}
								}
								ob_start();
								?>
                                <div<?php bp_field_css_class( 'editfield' );
								bp_field_data_attribute(); ?>>
                                    <fieldset>
										<?php
										$field_type = bp_xprofile_create_field_type( bp_get_the_profile_field_type() );
										$field_type->edit_field_html();
										?>
                                    </fieldset>
                                </div>
								<?php
								$profile_signup_fields[ bp_get_the_profile_field_id() ]['id']   = bp_get_the_profile_field_id();
								$profile_signup_fields[ bp_get_the_profile_field_id() ]['html'] .= ob_get_clean();
							endwhile;
							$profile_signup_field_ids = bp_get_the_profile_field_ids();
						endwhile;
					}

					// Merge account and xprofile signup fields.
					$merged_signup_fields = array_merge( $account_signup_fields, $profile_signup_fields );
					$xprofile_order       = get_option( 'bp_xprofile_fields_order' );

					if ( ! empty( $xprofile_order ) ) {
						$order_signup_fields = array();
						if ( ! empty( $merged_signup_fields ) ) {
							foreach ( $merged_signup_fields as $field ) {
								$order_signup_fields[ $field['id'] ] = $field;
							}
						}
						$merged_signup_fields = array_replace( array_flip( $xprofile_order ), $order_signup_fields );
					}
					?>
				</div><!-- #basic-details-section -->

				<?php bp_nouveau_signup_hook( 'after', 'account_details' ); ?>

				<?php /***** Extra Profile Details ******/ ?>

				<?php if ( bp_is_active( 'xprofile' ) && bp_nouveau_base_account_has_xprofile() ) : ?>

					<?php bp_nouveau_signup_hook( 'before', 'signup_profile' ); ?>

                    <div class="register-section extended-profile" id="profile-details-section">
						<?php
						if ( ! empty( $merged_signup_fields ) ) {
							foreach ( $merged_signup_fields as $order_signup_field ) {
								echo $order_signup_field['html'];
							}
						}
						?>
                        <input type="hidden" name="signup_profile_field_ids" id="signup_profile_field_ids"
                               value="<?php echo esc_attr( $profile_signup_field_ids ); ?>"/>
						<?php bp_nouveau_signup_hook( '', 'signup_profile' ); ?>
                    </div><!-- #profile-details-section -->

					<?php bp_nouveau_signup_hook( 'after', 'signup_profile' ); ?>
				<?php endif; ?>

				<?php if ( bp_get_blog_signup_allowed() ) : ?>

					<?php bp_nouveau_signup_hook( 'before', 'blog_details' ); ?>

					<?php /***** Blog Creation Details ******/ ?>

					<div class="register-section blog-details" id="blog-details-section">

						<h2><?php esc_html_e( 'Site Details', 'buddyboss' ); ?></h2>

						<p>
							<input type="checkbox" name="signup_with_blog" id="signup_with_blog" class="bs-styled-checkbox" value="1" <?php checked( (int) bp_get_signup_with_blog_value(), 1 ); ?> />
							<label for="signup_with_blog"><?php esc_html_e( "Yes, I'd like to create a new site", 'buddyboss' ); ?></label>
						</p>

						<div id="blog-details"<?php if ( (int) bp_get_signup_with_blog_value() ) : ?>class="show"<?php endif; ?>>

							<?php bp_nouveau_signup_form( 'blog_details' ); ?>

						</div>

					</div><!-- #blog-details-section -->

					<?php bp_nouveau_signup_hook( 'after', 'blog_details' ); ?>

				<?php endif; ?>

				</div><!-- //.layout-wrap -->

                <?php bp_nouveau_signup_terms_privacy(); ?>

				<?php bp_nouveau_submit_button( 'register' ); ?>

			<?php endif; // request-details signup step ?>

			<?php bp_nouveau_signup_hook( 'custom', 'steps' ); ?>

			</form>

	</div>

	<?php bp_nouveau_signup_hook( 'after', 'page' ); ?>
