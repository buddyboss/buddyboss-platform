<?php

/**
 * Move Reply
 *
 * @package BuddyBoss\Theme
 */

?>

<div id="bbpress-forums">

	<?php bbp_breadcrumb(); ?>

	<?php if ( is_user_logged_in() && current_user_can( 'edit_topic', bbp_get_topic_id() ) ) : ?>

		<div id="move-reply-<?php bbp_topic_id(); ?>" class="bbp-reply-move">

			<form id="move_reply" name="move_reply" method="post" action="<?php the_permalink(); ?>">

				<fieldset class="bbp-form">

					<legend><?php printf( __( 'Move reply "%s"', 'buddyboss' ), bbp_get_reply_title() ); ?></legend>

					<div>

						<div class="bp-feedback info">
							<span class="bp-icon" aria-hidden="true"></span>
							<p><?php _e( 'You can either make this reply a new discussion with a new title, or merge it into an existing discussion.', 'buddyboss' ); ?></p>
						</div>

						<div class="bp-feedback info">
							<span class="bp-icon" aria-hidden="true"></span>
							<p><?php _e( 'If you choose an existing discussion, replies will be ordered by the time and date they were created.', 'buddyboss' ); ?></p>
						</div>

						<fieldset class="bbp-form">
							<legend><?php _e( 'Move Method', 'buddyboss' ); ?></legend>

							<div>
								<div class="bp-radio-wrap">
									<input name="bbp_reply_move_option" id="bbp_reply_move_option_reply" class="bs-styled-radio" type="radio" checked="checked" value="topic" tabindex="<?php bbp_tab_index(); ?>" />
									<label for="bbp_reply_move_option_reply"><?php printf( __( 'New discussion in <strong>%s</strong> titled:', 'buddyboss' ), bbp_get_forum_title( bbp_get_reply_forum_id( bbp_get_reply_id() ) ) ); ?></label>
								</div>
								<input type="text" id="bbp_reply_move_destination_title" value="<?php printf( __( 'Moved: %s', 'buddyboss' ), bbp_get_reply_title() ); ?>" tabindex="<?php bbp_tab_index(); ?>" size="35" name="bbp_reply_move_destination_title" />
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
										<label for="bbp_reply_move_option_existing"><?php _e( 'Use an existing discussion in this forum:', 'buddyboss' ); ?></label>
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
							<p><?php _e( '<strong>WARNING:</strong> This process cannot be undone.', 'buddyboss' ); ?></p>
						</div>

						<div class="bbp-submit-wrapper">
							<button type="submit" tabindex="<?php bbp_tab_index(); ?>" id="bbp_move_reply_submit" name="bbp_move_reply_submit" class="button submit"><?php _e( 'Submit', 'buddyboss' ); ?></button>
						</div>
					</div>

					<?php bbp_move_reply_form_fields(); ?>

				</fieldset>
			</form>
		</div>

	<?php else : ?>

		<div id="no-reply-<?php bbp_reply_id(); ?>" class="bbp-no-reply">
			<div class="entry-content"><?php is_user_logged_in() ? _e( 'You do not have the permissions to edit this reply!', 'buddyboss' ) : _e( 'You cannot edit this reply.', 'buddyboss' ); ?></div>
		</div>

	<?php endif; ?>

</div>
