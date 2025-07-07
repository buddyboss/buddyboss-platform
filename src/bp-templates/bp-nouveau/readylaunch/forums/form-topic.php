<?php
/**
 * New/Edit Topic Form Template
 *
 * @package BuddyBoss\Template
 * @subpackage BP_Nouveau\ReadyLaunch
 * @since BuddyBoss 2.9.00
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;
?>

<?php if ( ! bbp_is_single_forum() ) : ?>

<div id="bbpress-forums">

<?php endif; ?>

<?php if ( bbp_is_topic_edit() ) : ?>

	<?php bbp_topic_tag_list( bbp_get_topic_id() ); ?>

<?php endif; ?>

<?php if ( bbp_current_user_can_access_create_topic_form() ) : ?>

	<div id="new-topic-<?php bbp_topic_id(); ?>" class="bbp-topic-form bb-rl-forum-modal <?php echo ( bbp_is_topic_edit() ? 'bb-rl-forum-modal--static' : '' ); ?>">

		<form id="new-post" class="bb-rl-forum-form" name="new-post" method="post" action="<?php bbp_is_topic_edit() ? bbp_topic_edit_url() : the_permalink(); ?>">

			<?php do_action( 'bbp_theme_before_topic_form' ); ?>

			<fieldset class="bbp-form">
				<header class="bb-rl-forum-modal-header">

					<h3>
						<?php
						if ( bbp_is_topic_edit() ) {
							esc_html_e( 'Edit Discussion', 'buddyboss' );
						} else {
							esc_html_e( 'Start a new discussion', 'buddyboss' );
						}
						?>
					</h3>

					<?php if ( current_user_can( 'moderate' ) ) : ?>

						<?php do_action( 'bbp_theme_before_topic_form_type' ); ?>

						<div class="bb-rl-forum-modal-type">

							<label for="bbp_stick_topic" class="screen-reader-text"><?php esc_html_e( 'Type:', 'buddyboss' ); ?></label>

							<?php bbp_form_topic_type_dropdown(); ?>

						</div>

						<?php do_action( 'bbp_theme_after_topic_form_type' ); ?>

					<?php endif; ?>

					<button type="button" class="bb-rl-forum-modal-close">
						<span class="screen-reader-text"><?php esc_html_e( 'Close Modal', 'buddyboss' ); ?></span>
						<span class="bb-icons-rl-x"></span>
					</button>

				</header>

				<?php do_action( 'bbp_theme_before_topic_form_notices' ); ?>

				<?php if ( ! bbp_is_topic_edit() && bbp_is_forum_closed() ) : ?>

					<div class="bp-feedback info">
						<span class="bp-icon" aria-hidden="true"></span>
						<p><?php esc_html_e( 'This forum is marked as closed to new discussions, however your posting capabilities still allow you to do so.', 'buddyboss' ); ?></p>
					</div>

				<?php endif; ?>

				<?php do_action( 'bbp_template_notices' ); ?>

				<div class="bb-rl-forum-modal-content">

					<div class="bb-rl-forum-form-anonymous">
						<?php bbp_get_template_part( 'form', 'anonymous' ); ?>
					</div>

					<?php do_action( 'bbp_theme_before_topic_form_title' ); ?>

					<div class="bb-rl-forum-modal-title">
						<label for="bbp_topic_title" class="screen-reader-text"><?php esc_html_e( 'Discussion Title', 'buddyboss' ); ?></label>
						<input type="text" id="bbp_topic_title" placeholder="<?php esc_attr_e( 'Title', 'buddyboss' ); ?>" value="<?php bbp_form_topic_title(); ?>" tabindex="<?php bbp_tab_index(); ?>" size="40" name="bbp_topic_title" maxlength="<?php bbp_title_max_length(); ?>" />
					</div>

					<?php do_action( 'bbp_theme_after_topic_form_title' ); ?>

					<?php do_action( 'bbp_theme_before_topic_form_content' ); ?>

					<?php bbp_the_content( array( 'context' => 'topic' ) ); ?>

					<?php do_action( 'bbp_theme_after_topic_form_content' ); ?>

					<?php if ( ! ( bbp_use_wp_editor() || current_user_can( 'unfiltered_html' ) ) ) : ?>

						<p class="form-allowed-tags">
							<label><?php esc_html_e( 'You may use these <abbr title="HyperText Markup Language">HTML</abbr> tags and attributes:', 'buddyboss' ); ?></label><br />
							<code><?php bbp_allowed_tags(); ?></code>
						</p>

					<?php endif; ?>

					<?php if ( bbp_allow_topic_tags() && current_user_can( 'assign_topic_tags' ) ) : ?>

						<?php do_action( 'bbp_theme_before_topic_form_tags' ); ?>

						<?php
						$get_topic_id = bbp_get_topic_id();
						$get_the_tags = isset( $get_topic_id ) && ! empty( $get_topic_id ) ? bbp_get_topic_tag_names( $get_topic_id ) : array();
						?>

						<p class="bb-rl-forum-tags">
							<input type="hidden" value="<?php echo ( ! empty( $get_the_tags ) ) ? esc_attr( $get_the_tags ) : ''; ?>" name="bbp_topic_tags" class="bbp_topic_tags" id="bbp_topic_tags" >
							<select name="bbp_topic_tags_dropdown[]" class="bbp_topic_tags_dropdown" id="bbp_topic_tags_dropdown" placeholder="<?php esc_attr_e( 'Enter tags, separated by commas (optional)', 'buddyboss' ); ?>" autocomplete="off" multiple="multiple" style="width: 100%" tabindex="<?php bbp_tab_index(); ?>">
								<?php
								if ( ! empty( $get_the_tags ) ) {
									$get_the_tags = explode( ',', $get_the_tags );
									foreach ( $get_the_tags as $single_tag ) {
										$single_tag = trim( $single_tag );
										?>
										<option selected="selected" value="<?php echo esc_attr( $single_tag ); ?>"><?php echo esc_html( $single_tag ); ?></option>
										<?php
									}
								}
								?>
							</select>
						</p>

						<?php do_action( 'bbp_theme_after_topic_form_tags' ); ?>

					<?php endif; ?>

					<?php if ( ! bbp_is_single_forum() ) : ?>

						<?php do_action( 'bbp_theme_before_topic_form_forum' ); ?>

						<p class="bb-rl-forum-id-selection">
							<label for="bbp_forum_id"><?php esc_html_e( 'Forum:', 'buddyboss' ); ?></label><br />
							<?php
								bbp_dropdown(
									array(
										'show_none' => __( '(No Forum)', 'buddyboss' ),
										'selected'  => bbp_get_form_topic_forum(),
									)
								);
							?>
						</p>

						<?php do_action( 'bbp_theme_after_topic_form_forum' ); ?>

					<?php endif; ?>

					<?php if ( bbp_allow_revisions() && bbp_is_topic_edit() ) : ?>

						<?php do_action( 'bbp_theme_before_topic_form_revisions' ); ?>

						<fieldset class="bbp-form">
							<div class="bp-checkbox-wrap">
								<input name="bbp_log_topic_edit" id="bbp_log_topic_edit" class="bs-styled-checkbox" type="checkbox" value="1" <?php bbp_form_topic_log_edit(); ?> tabindex="<?php bbp_tab_index(); ?>" />
								<label for="bbp_log_topic_edit"><?php esc_html_e( 'Keep a log of this edit:', 'buddyboss' ); ?></label><br />
							</div>

							<div class="bb-rl-forum-edit-reason">
								<label for="bbp_topic_edit_reason"><?php printf( esc_html__( 'Optional reason for editing:', 'buddyboss' ), bbp_get_current_user_name() ); ?></label><br />
								<input type="text" value="<?php bbp_form_topic_edit_reason(); ?>" tabindex="<?php bbp_tab_index(); ?>" size="40" name="bbp_topic_edit_reason" id="bbp_topic_edit_reason" />
							</div>
						</fieldset>

						<?php do_action( 'bbp_theme_after_topic_form_revisions' ); ?>

					<?php endif; ?>

				</div>

				<div class="bb-rl-forum-modal-footer">

					<?php bbp_get_template_part( 'form', 'attachments' ); ?>

					<?php if ( bb_is_enabled_subscription( 'forum' ) && ! bbp_is_anonymous() && ( ! bbp_is_topic_edit() || ( bbp_is_topic_edit() && ! bbp_is_topic_anonymous() ) ) ) : ?>

						<?php
						if (
						bb_enabled_legacy_email_preference() ||
						( ! bb_enabled_legacy_email_preference() && bb_get_modern_notification_admin_settings_is_enabled( 'bb_forums_subscribed_discussion' ) )
						) {
							?>

							<?php do_action( 'bbp_theme_before_topic_form_subscriptions' ); ?>

						<p class="checkbox bp-checkbox-wrap bb-rl-forum-subscription">
							<input name="bbp_topic_subscription" id="bbp_topic_subscription" class="bs-styled-checkbox" type="checkbox" value="bbp_subscribe" <?php bbp_form_topic_subscribed(); ?> tabindex="<?php bbp_tab_index(); ?>" />

							<?php
							if ( bbp_is_topic_edit() && ( bbp_get_topic_author_id() !== bbp_get_current_user_id() ) ) :
								if (
									(
										function_exists( 'bb_enabled_legacy_email_preference' ) && bb_enabled_legacy_email_preference()
									) ||
									(
										function_exists( 'bp_is_active' ) &&
										! bp_is_active( 'notifications' )
									)
								) {
									?>
									<label for="bbp_topic_subscription"><?php esc_html_e( 'Notify the author of follow-up replies via email', 'buddyboss' ); ?></label>
									<?php
								} else {
									?>
									<label for="bbp_topic_subscription"><?php esc_html_e( 'Notify the author of follow-up replies', 'buddyboss' ); ?></label>
									<?php
								}
								elseif (
									(
										function_exists( 'bb_enabled_legacy_email_preference' ) && bb_enabled_legacy_email_preference()
									) ||
									(
										function_exists( 'bp_is_active' ) &&
										! bp_is_active( 'notifications' )
									)
									) :

									?>
									<label for="bbp_topic_subscription"><?php esc_html_e( 'Notify me of new replies by email', 'buddyboss' ); ?></label>
										<?php
									else :
										?>
									<label for="bbp_topic_subscription"><?php esc_html_e( 'Notify me of new replies', 'buddyboss' ); ?></label>
										<?php

									endif;
									?>
						</p>

							<?php do_action( 'bbp_theme_after_topic_form_subscriptions' ); ?>

						<?php } ?>

					<?php endif; ?>
					<?php do_action( 'bbp_theme_before_topic_form_submit_wrapper' ); ?>
					<div class="bbp-submit-wrapper">

						<?php do_action( 'bbp_theme_before_topic_form_submit_button' ); ?>

						<button type="button" tabindex="<?php bbp_tab_index(); ?>" id="bb_topic_discard_draft" name="bb_topic_discard_draft" class="button discard small bb_discard_topic_reply_draft"><?php esc_html_e( 'Discard Draft', 'buddyboss' ); ?></button>

						<button type="submit" tabindex="<?php bbp_tab_index(); ?>" id="bbp_topic_submit" name="bbp_topic_submit" class="bb-rl-button bb-rl-button--brandFill bb-rl-button--small submit">
							<?php
							if ( bbp_is_topic_edit() ) {
								esc_html_e( 'Update', 'buddyboss' );
							} else {
								esc_html_e( 'Post', 'buddyboss' );
							}
							?>
						</button>

						<?php do_action( 'bbp_theme_after_topic_form_submit_button' ); ?>

					</div>
				</div>
				<?php bbp_topic_form_fields(); ?>

				<?php do_action( 'bbp_theme_after_topic_form_submit_wrapper' ); ?>


			</fieldset>

			<?php do_action( 'bbp_theme_after_topic_form' ); ?>

		</form>

		<div class="bb-rl-forum-modal-overlay"></div>
	</div>

<?php elseif ( bbp_is_forum_closed() ) : ?>

	<div id="no-topic-<?php bbp_topic_id(); ?>" class="bbp-no-topic">
		<div class="bp-feedback info">
			<span class="bp-icon" aria-hidden="true"></span>
			<p>
				<?php
				/* translators: %s: Forum title */
				printf( esc_html__( 'The forum "%s" is closed to new discussions and replies.', 'buddyboss' ), bbp_get_forum_title() );
				?>
			</p>
		</div>
	</div>

<?php else : ?>

	<div id="no-topic-<?php bbp_topic_id(); ?>" class="bbp-no-topic">
		<div class="bp-feedback info">
			<span class="bp-icon" aria-hidden="true"></span>
			<p><?php is_user_logged_in() ? esc_html_e( 'You cannot create new discussions.', 'buddyboss' ) : esc_html_e( 'You must be logged in to create new discussions.', 'buddyboss' ); ?></p>
		</div>
	</div>

<?php endif; ?>

<?php if ( ! bbp_is_single_forum() ) : ?>

</div>

<?php endif; ?>
