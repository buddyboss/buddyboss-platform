<?php

/**
 * New/Edit Forum
 *
 * @package BuddyBoss\Theme
 */

?>
<div id="bbpress-forums">
<?php if ( bbp_is_forum_edit() ) : ?>



	<?php bbp_breadcrumb(); ?>

<?php endif; ?>

<?php if ( bbp_current_user_can_access_create_forum_form() ) : ?>

	<div id="new-forum-<?php bbp_forum_id(); ?>" class="bbp-forum-form">

		<form id="new-post" name="new-post" method="post" action="<?php the_permalink(); ?>">

			<?php do_action( 'bbp_theme_before_forum_form' ); ?>

			<fieldset class="bbp-form">
				<h2 class="bbp-form-title entry-title">
				<?php
				if ( bbp_is_forum_edit() ) {
					printf( __( 'Now Editing &ldquo;%s&rdquo;', 'buddyboss' ), bbp_get_forum_title() );
				} else {
					bbp_is_single_forum() ? printf( __( 'Create New Forum in &ldquo;%s&rdquo;', 'buddyboss' ), bbp_get_forum_title() ) : _e( 'Create New Forum', 'buddyboss' );
				}
				?>
				</h2>

				<?php do_action( 'bbp_theme_before_forum_form_notices' ); ?>

				<?php if ( ! bbp_is_forum_edit() && bbp_is_forum_closed() ) : ?>

					<div class="bp-feedback info">
						<span class="bp-icon" aria-hidden="true"></span>
						<p><?php _e( 'This forum is closed to new content, however your account still allows you to do so.', 'buddyboss' ); ?></p>
					</div>

				<?php endif; ?>

				<?php do_action( 'bbp_template_notices' ); ?>

				<div>

					<?php do_action( 'bbp_theme_before_forum_form_title' ); ?>

					<p>
						<label class="bbp-forum-title-label" for="bbp_forum_title"><?php printf( __( 'Forum Name <span>(Maximum Length: %d)</span>', 'buddyboss' ), bbp_get_title_max_length() ); ?></label><br />
						<input type="text" id="bbp_forum_title" value="<?php bbp_form_forum_title(); ?>" tabindex="<?php bbp_tab_index(); ?>" size="40" name="bbp_forum_title" maxlength="<?php bbp_title_max_length(); ?>" />
					</p>

					<?php do_action( 'bbp_theme_after_forum_form_title' ); ?>

					<?php do_action( 'bbp_theme_before_forum_form_content' ); ?>

					<label><?php _e( 'Description', 'buddyboss' ); ?></label><br />
					<?php bbp_the_content( array( 'context' => 'forum' ) ); ?>

					<?php do_action( 'bbp_theme_after_forum_form_content' ); ?>

					<?php bbp_get_template_part( 'form', 'forum-attachments' ); ?>

					<?php if ( ! ( bbp_use_wp_editor() || current_user_can( 'unfiltered_html' ) ) ) : ?>

						<p class="form-allowed-tags">
							<label><?php _e( 'You may use these <abbr title="HyperText Markup Language">HTML</abbr> tags and attributes:', 'buddyboss' ); ?></label><br />
							<code><?php bbp_allowed_tags(); ?></code>
						</p>

					<?php endif; ?>

					<div class="bp-forum-settings">
						<?php do_action( 'bbp_theme_before_forum_form_type' ); ?>
						<p>
							<label for="bbp_forum_type"><?php _e( 'Forum Type', 'buddyboss' ); ?></label><br />
							<?php bbp_form_forum_type_dropdown(); ?>
						</p>
						<?php do_action( 'bbp_theme_after_forum_form_type' ); ?>

						<?php do_action( 'bbp_theme_before_forum_form_status' ); ?>
						<p>
							<label for="bbp_forum_status"><?php _e( 'Status', 'buddyboss' ); ?></label><br />
							<?php bbp_form_forum_status_dropdown(); ?>
						</p>
						<?php do_action( 'bbp_theme_after_forum_form_status' ); ?>

						<?php do_action( 'bbp_theme_before_forum_form_status' ); ?>
						<p>
							<label for="bbp_forum_visibility"><?php _e( 'Visibility', 'buddyboss' ); ?></label><br />
							<?php bbp_form_forum_visibility_dropdown(); ?>
						</p>
						<?php do_action( 'bbp_theme_after_forum_visibility_status' ); ?>

						<?php do_action( 'bbp_theme_before_forum_form_parent' ); ?>
						<p>
							<label for="bbp_forum_parent_id"><?php _e( 'Parent Forum', 'buddyboss' ); ?></label><br />

							<?php
								bbp_dropdown(
									array(
										'select_id' => 'bbp_forum_parent_id',
										'show_none' => __( '(No Parent)', 'buddyboss' ),
										'selected'  => bbp_get_form_forum_parent(),
										'exclude'   => bbp_get_forum_id(),
									)
								);
							?>
						</p>
						<?php do_action( 'bbp_theme_after_forum_form_parent' ); ?>
					</div>

					<?php do_action( 'bbp_theme_before_forum_form_submit_wrapper' ); ?>

					<div class="bbp-submit-wrapper">

						<?php do_action( 'bbp_theme_before_forum_form_submit_button' ); ?>

						<button type="submit" tabindex="<?php bbp_tab_index(); ?>" id="bbp_forum_submit" name="bbp_forum_submit" class="button submit"><?php _e( 'Submit', 'buddyboss' ); ?></button>

						<?php do_action( 'bbp_theme_after_forum_form_submit_button' ); ?>

					</div>

					<?php do_action( 'bbp_theme_after_forum_form_submit_wrapper' ); ?>

				</div>

				<?php bbp_forum_form_fields(); ?>

			</fieldset>

			<?php do_action( 'bbp_theme_after_forum_form' ); ?>

		</form>
	</div>

<?php elseif ( bbp_is_forum_closed() ) : ?>

	<div id="no-forum-<?php bbp_forum_id(); ?>" class="bbp-no-forum">
		<div class="bp-feedback info">
			<span class="bp-icon" aria-hidden="true"></span>
			<p><?php printf( __( 'The forum "%s" is closed to new content.', 'buddyboss' ), bbp_get_forum_title() ); ?></p>
		</div>
	</div>

<?php else : ?>

	<div id="no-forum-<?php bbp_forum_id(); ?>" class="bbp-no-forum">
		<div class="bp-feedback info">
			<span class="bp-icon" aria-hidden="true"></span>
			<p><?php is_user_logged_in() ? _e( 'You cannot create new forums.', 'buddyboss' ) : _e( 'You must be logged in to create new forums.', 'buddyboss' ); ?></p>
		</div>
	</div>

<?php endif; ?>


</div>

