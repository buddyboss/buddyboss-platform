<?php
/**
 * New/Edit Reply
 *
 * @package BuddyBoss\Template
 * @subpackage BP_Nouveau\ReadyLaunch
 * @since BuddyBoss 2.9.00
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;
?>

<?php if ( bbp_is_reply_edit() ) : ?>

<div id="bbpress-forums">

<?php endif; ?>

<?php if ( bbp_current_user_can_access_create_reply_form() ) : ?>

	<div id="new-reply-<?php bbp_topic_id(); ?>" class="bbp-reply-form bb-rl-forum-modal <?php echo ( bbp_is_reply_edit() ? 'bb-rl-forum-modal--static' : '' ); ?>">

		<form id="new-post" class="bb-rl-forum-form" name="new-post" method="post" action="<?php bbp_is_reply_edit() ? bbp_reply_edit_url() : the_permalink(); ?>">

			<?php do_action( 'bbp_theme_before_reply_form' ); ?>

			<fieldset class="bbp-form">
				<div class="bb-rl-forum-modal-header">
					<?php if ( ! bbp_is_reply_edit() ) : ?>
						<h3><?php esc_html_e( 'Replying to', 'buddyboss' ); ?></h3>
					<?php else : ?>
						<h3><?php esc_html_e( 'Edit reply', 'buddyboss' ); ?></h3>
					<?php endif; ?>
					<button type="button" class="bb-rl-forum-modal-close">
						<span class="screen-reader-text">Close Modal</span>
						<span class="bb-icons-rl-x"></span>
					</button>
				</div>

				<?php do_action( 'bbp_theme_before_reply_form_notices' ); ?>

				<?php if ( ! bbp_is_topic_open() && ! bbp_is_reply_edit() ) : ?>

					<div class="bp-feedback info">
						<span class="bp-icon" aria-hidden="true"></span>
						<p><?php esc_html_e( 'This discussion is marked as closed to new replies, however your posting capabilities still allow you to do so.', 'buddyboss' ); ?></p>
					</div>

				<?php endif; ?>

				<?php do_action( 'bbp_template_notices' ); ?>

				<div class="bb-rl-forum-modal-content">

					<?php if ( ! bbp_is_reply_edit() ) : ?>
						<div class="bb-rl-reply-header">
							<div class="bb-rl-reply-header-avatar">
								<img class="bb-rl-avatar" alt="<?php esc_attr_e( 'Reply author avatar', 'buddyboss' ); ?>" />
							</div>
							<div class="bb-rl-reply-header-content">
								<h4 class="bb-rl-reply-header-title"></h4>
								<p class="bb-rl-reply-header-excerpt"></p>
							</div>
						</div>
					<?php else : ?>
						<div class="bb-rl-reply-header">
							<div class="bb-rl-reply-header-avatar">
								<?php bbp_topic_author_link( array( 'size' => '48' ) ); ?>
							</div>
							<div class="bb-rl-reply-header-content">
								<h4 class="bb-rl-reply-header-title"><?php echo bbp_get_topic_author_display_name(); ?></h4>
								<p class="bb-rl-reply-header-excerpt"><?php bbp_reply_excerpt( bbp_get_topic_id(), 50 ); ?></p>
							</div>
						</div>
					<?php endif; ?>

					<?php bbp_get_template_part( 'form', 'anonymous' ); ?>

					<?php do_action( 'bbp_theme_before_reply_form_content' ); ?>

					<?php bbp_the_content( array( 'context' => 'reply' ) ); ?>

					<?php do_action( 'bbp_theme_after_reply_form_content' ); ?>

					<?php if ( ! ( bbp_use_wp_editor() || current_user_can( 'unfiltered_html' ) ) ) : ?>

						<p class="form-allowed-tags">
							<label><?php _e( 'You may use these <abbr title="HyperText Markup Language">HTML</abbr> tags and attributes:', 'buddyboss' ); ?></label><br />
							<code><?php bbp_allowed_tags(); ?></code>
						</p>

					<?php endif; ?>

					<?php if ( bbp_allow_topic_tags() && current_user_can( 'assign_topic_tags' ) ) : ?>

						<?php do_action( 'bbp_theme_before_reply_form_tags' ); ?>

						<?php
						$get_topic_id = bbp_get_topic_id();
						$get_the_tags = isset( $get_topic_id ) && ! empty( $get_topic_id ) ? bbp_get_topic_tag_names( $get_topic_id ) : array();
						?>

						<p class="bb-rl-forum-tags">
							<input type="hidden" value="<?php echo ( ! empty( $get_the_tags ) ) ? esc_attr( $get_the_tags ) : ''; ?>" name="bbp_topic_tags" class="bbp_topic_tags" id="bbp_topic_tags" >
							<select name="bbp_topic_tags_dropdown[]" class="bbp_topic_tags_dropdown" id="bbp_topic_tags_dropdown" placeholder="<?php esc_html_e( 'Type one or more tags, comma separated', 'buddyboss' ); ?>" autocomplete="off" multiple="multiple" style="width: 100%" tabindex="<?php bbp_tab_index(); ?>">
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

						<?php do_action( 'bbp_theme_after_reply_form_tags' ); ?>

					<?php endif; ?>

					<?php if ( bbp_allow_revisions() && bbp_is_reply_edit() ) : ?>

						<?php do_action( 'bbp_theme_before_reply_form_revisions' ); ?>

					<fieldset class="bbp-form bb-rl-reply-log-edit">
						<div class="bp-checkbox-wrap">
							<input name="bbp_log_reply_edit" id="bbp_log_reply_edit" class="bs-styled-checkbox" type="checkbox" value="1" <?php bbp_form_reply_log_edit(); ?> tabindex="<?php bbp_tab_index(); ?>" />
							<label for="bbp_log_reply_edit"><?php esc_html_e( 'Keep a log of this edit:', 'buddyboss' ); ?></label>
						</div>

						<div class="bb-rl-forum-edit-reason">
							<label for="bbp_reply_edit_reason"><?php printf( esc_html__( 'Optional reason for editing:', 'buddyboss' ), bbp_get_current_user_name() ); ?></label><br />
							<input type="text" value="<?php bbp_form_reply_edit_reason(); ?>" tabindex="<?php bbp_tab_index(); ?>" size="40" name="bbp_reply_edit_reason" id="bbp_reply_edit_reason" />
						</div>
					</fieldset>

						<?php do_action( 'bbp_theme_after_reply_form_revisions' ); ?>

					<?php endif; ?>

				</div>

				<div class="bb-rl-forum-modal-footer">
					<?php bbp_get_template_part( 'form', 'attachments' ); ?>

					<?php if ( bb_is_enabled_subscription( 'topic' ) && ! bbp_is_anonymous() && ( ! bbp_is_reply_edit() || ( bbp_is_reply_edit() && ! bbp_is_reply_anonymous() ) ) ) : ?>

						<?php
						if (
							bb_enabled_legacy_email_preference() ||
							( ! bb_enabled_legacy_email_preference() && bb_get_modern_notification_admin_settings_is_enabled( 'bb_forums_subscribed_reply' ) )
						) {
							?>

							<?php do_action( 'bbp_theme_before_reply_form_subscription' ); ?>

							<p class="checkbox bp-checkbox-wrap bb-rl-forum-subscription">

								<input name="bbp_topic_subscription" id="bbp_topic_subscription" class="bs-styled-checkbox" type="checkbox" value="bbp_subscribe"<?php bbp_form_topic_subscribed(); ?> tabindex="<?php bbp_tab_index(); ?>" />

								<?php
								if ( bbp_is_reply_edit() && ( bbp_get_reply_author_id() !== bbp_get_current_user_id() ) ) :

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

							<?php do_action( 'bbp_theme_after_reply_form_subscription' ); ?>

						<?php } ?>

					<?php endif; ?>

					<?php do_action( 'bbp_theme_before_reply_form_submit_wrapper' ); ?>

					<div class="bbp-submit-wrapper">

						<?php do_action( 'bbp_theme_before_reply_form_submit_button' ); ?>

						<?php bbp_cancel_reply_to_link(); ?>

						<button type="button" tabindex="<?php bbp_tab_index(); ?>" id="bb_reply_discard_draft" name="bb_reply_discard_draft" class="bb-rl-button bb-rl-button--tertiaryText bb-rl-button--small discard small bb_discard_topic_reply_draft"><?php esc_html_e( 'Discard Draft', 'buddyboss' ); ?></button>

						<button type="submit" tabindex="<?php bbp_tab_index(); ?>" id="bbp_reply_submit" name="bbp_reply_submit" class="bb-rl-button bb-rl-button--brandFill bb-rl-button--small submit"><?php esc_html_e( 'Post', 'buddyboss' ); ?></button>

						<?php do_action( 'bbp_theme_after_reply_form_submit_button' ); ?>

					</div>

					<?php do_action( 'bbp_theme_after_reply_form_submit_wrapper' ); ?>

				</div>

				<?php bbp_reply_form_fields(); ?>

			</fieldset>

			<?php do_action( 'bbp_theme_after_reply_form' ); ?>

		</form>
		<div class="bb-rl-forum-modal-overlay"></div>
	</div>

<?php elseif ( bbp_is_topic_closed() ) : ?>

	<div id="no-reply-<?php bbp_topic_id(); ?>" class="bbp-no-reply">
		<div class="bp-feedback info">
			<span class="bp-icon" aria-hidden="true"></span>
			<p>
				<?php
				/* translators: %s: Topic title */
				printf( esc_html__( 'The discussion "%s" is closed to new replies.', 'buddyboss' ), bbp_get_topic_title() );
				?>
			</p>
		</div>
	</div>

<?php elseif ( bbp_is_forum_closed( bbp_get_topic_forum_id() ) ) : ?>

	<div id="no-reply-<?php bbp_topic_id(); ?>" class="bbp-no-reply">
		<div class="bp-feedback info">
			<span class="bp-icon" aria-hidden="true"></span>
			<p>
				<?php
				/* translators: %s: Forum title */
				printf( esc_html__( 'The forum "%s" is closed to new discussions and replies.', 'buddyboss' ), bbp_get_forum_title( bbp_get_topic_forum_id() ) );
				?>
			</p>
		</div>
	</div>

<?php else : ?>

	<div id="no-reply-<?php bbp_topic_id(); ?>" class="bbp-no-reply">
		<div class="bp-feedback info">
			<span class="bp-icon" aria-hidden="true"></span>
			<p><?php is_user_logged_in() ? esc_html_e( 'You cannot reply to this discussion.', 'buddyboss' ) : esc_html_e( 'Log in  to reply.', 'buddyboss' ); ?></p>
		</div>
	</div>

<?php endif; ?>

<?php if ( bbp_is_reply_edit() ) : ?>

</div>

<?php endif; ?>
