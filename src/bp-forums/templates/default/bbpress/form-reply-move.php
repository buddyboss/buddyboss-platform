<?php

/**
 * Move Reply
 *
 * @package BuddyBoss\Theme
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

?>

<div id="bbpress-forums">

	<?php bbp_breadcrumb(); ?>

	<?php if ( is_user_logged_in() && current_user_can( 'edit_topic', bbp_get_topic_id() ) ) : ?>

		<div id="move-reply-<?php bbp_topic_id(); ?>" class="bbp-reply-move">

			<form id="move_reply" name="move_reply" method="post" action="<?php the_permalink(); ?>">

				<fieldset class="bbp-form">

					<legend><?php /* translators: %s: reply title. */ printf( esc_html__( 'Move reply "%s"', 'buddyboss-platform' ), bbp_get_reply_title() ); ?></legend>

					<div>

						<div class="bp-feedback info">
							<span class="bp-icon" aria-hidden="true"></span>
							<p><?php esc_html_e( 'You can either make this reply a new discussion with a new title, or merge it into an existing discussion.', 'buddyboss-platform' ); ?></p>
						</div>

						<div class="bp-feedback info">
							<span class="bp-icon" aria-hidden="true"></span>
							<p><?php esc_html_e( 'If you choose an existing discussion, replies will be ordered by the time and date they were created.', 'buddyboss-platform' ); ?></p>
						</div>

						<fieldset class="bbp-form">
							<legend><?php esc_html_e( 'Move Method', 'buddyboss-platform' ); ?></legend>

							<div>
								<div class="bp-radio-wrap">
									<input name="bbp_reply_move_option" id="bbp_reply_move_option_reply" class="bs-styled-radio" type="radio" checked="checked" value="topic" tabindex="<?php bbp_tab_index(); ?>" />
									<label for="bbp_reply_move_option_reply"><?php /* translators: %s: forum title. */ printf( __( 'New discussion in <strong>%s</strong> titled:', 'buddyboss-platform' ), bbp_get_forum_title( bbp_get_reply_forum_id( bbp_get_reply_id() ) ) ); ?></label>
								</div>
								<input type="text" id="bbp_reply_move_destination_title" value="<?php /* translators: %s: reply title. */ printf( esc_html__( 'Moved: %s', 'buddyboss-platform' ), bbp_get_reply_title() ); ?>" tabindex="<?php bbp_tab_index(); ?>" size="35" name="bbp_reply_move_destination_title" />
							</div>

							<?php
							if ( bbp_has_topics(
								array(
									'show_stickies' => false,
									'post_parent'   => bbp_get_reply_forum_id( bbp_get_reply_id() ),
									'post__not_in'  => array( bbp_get_reply_topic_id( bbp_get_reply_id() ) ),
								)
							) ) :
								?>

								<div>
									<div class="bp-radio-wrap">
										<input name="bbp_reply_move_option" id="bbp_reply_move_option_existing" class="bs-styled-radio" type="radio" value="existing" tabindex="<?php bbp_tab_index(); ?>" />
										<label for="bbp_reply_move_option_existing"><?php esc_html_e( 'Use an existing discussion in this forum:', 'buddyboss-platform' ); ?></label>
									</div>

									<?php
									bbp_dropdown(
										array(
											'post_type'   => bbp_get_topic_post_type(),
											'post_parent' => bbp_get_reply_forum_id( bbp_get_reply_id() ),
											'selected'    => -1,
											'exclude'     => bbp_get_reply_topic_id( bbp_get_reply_id() ),
											'select_id'   => 'bbp_destination_topic',
										)
									);
									?>
								</div>

							<?php endif; ?>

						</fieldset>

						<div class="bp-feedback error">
							<span class="bp-icon" aria-hidden="true"></span>
							<p><?php echo wp_kses_post( __( '<strong>WARNING:</strong> This process cannot be undone.', 'buddyboss-platform' ) ); ?></p>
						</div>

						<div class="bbp-submit-wrapper">
							<button type="submit" tabindex="<?php bbp_tab_index(); ?>" id="bbp_move_reply_submit" name="bbp_move_reply_submit" class="button submit"><?php esc_html_e( 'Submit', 'buddyboss-platform' ); ?></button>
						</div>
					</div>

					<?php bbp_move_reply_form_fields(); ?>

				</fieldset>
			</form>
		</div>

	<?php else : ?>

		<div id="no-reply-<?php bbp_reply_id(); ?>" class="bbp-no-reply">
			<div class="entry-content"><?php is_user_logged_in() ? esc_html_e( 'You do not have the permissions to edit this reply!', 'buddyboss-platform' ) : esc_html_e( 'You cannot edit this reply.', 'buddyboss-platform' ); ?></div>
		</div>

	<?php endif; ?>

</div>
