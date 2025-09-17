<?php

/**
 * Merge Topic
 *
 * @package BuddyBoss\Theme
 */

?>

<div id="bbpress-forums">

	<?php bbp_breadcrumb(); ?>

	<?php if ( is_user_logged_in() && current_user_can( 'edit_topic', bbp_get_topic_id() ) ) : ?>

		<div id="merge-topic-<?php bbp_topic_id(); ?>" class="bbp-topic-merge">

			<form id="merge_topic" name="merge_topic" method="post" action="<?php the_permalink(); ?>">

				<fieldset class="bbp-form">

					<legend><?php printf( __( 'Merge discussion "%s"', 'buddyboss' ), bbp_get_topic_title() ); ?></legend>

					<div>

						<div class="bp-feedback info">
							<span class="bp-icon" aria-hidden="true"></span>
							<p><?php _e( 'Select the discussion to merge this one into. The destination topic will remain the lead discussion, and this one will change into a reply.', 'buddyboss' ); ?><br/>
							<?php _e( 'To keep this discussion as the lead, go to the other discussion and use the merge tool from there instead.', 'buddyboss' ); ?></p>
						</div>

						<div class="bp-feedback info">
							<span class="bp-icon" aria-hidden="true"></span>
							<p><?php _e( 'All replies within both discussions will be merged chronologically. The order of the merged replies is based on the time and date they were posted. If the destination discussion was created after this one, it\'s post date will be updated to second earlier than this one.', 'buddyboss' ); ?></p>
						</div>

						<fieldset class="bbp-form">
							<legend><?php _e( 'Destination', 'buddyboss' ); ?></legend>
							<div>
								<?php
								if ( bbp_has_topics(
									array(
										'show_stickies' => false,
										'post_parent'   => bbp_get_topic_forum_id( bbp_get_topic_id() ),
										'post__not_in'  => array( bbp_get_topic_id() ),
									)
								) ) :
									?>

									<label for="bbp_destination_topic"><?php _e( 'Merge with this topic:', 'buddyboss' ); ?></label>

										<?php
										bbp_dropdown(
											array(
												'post_type' => bbp_get_topic_post_type(),
												'post_parent' => bbp_get_topic_forum_id( bbp_get_topic_id() ),
												'selected' => -1,
												'exclude'  => bbp_get_topic_id(),
												'select_id' => 'bbp_destination_topic',
											)
										);
										?>

								<?php else : ?>

									<label><?php _e( 'There are no other discussions in this forum to merge with.', 'buddyboss' ); ?></label>

								<?php endif; ?>

							</div>
						</fieldset>

						<fieldset class="bbp-form">
							<legend><?php _e( 'Discussion Extras', 'buddyboss' ); ?></legend>

							<div>
								<?php if ( bb_is_enabled_subscription( 'topic' ) ) : ?>
									<div class="bp-checkbox-wrap">
										<input name="bbp_topic_subscribers" id="bbp_topic_subscribers" class="bs-styled-checkbox" type="checkbox" value="1" checked="checked" tabindex="<?php bbp_tab_index(); ?>" />
										<label for="bbp_topic_subscribers"><?php _e( 'Merge discussion subscribers', 'buddyboss' ); ?></label>
									</div>
								<?php endif; ?>

								<div class="bp-checkbox-wrap">
									<input name="bbp_topic_favoriters" id="bbp_topic_favoriters" class="bs-styled-checkbox" type="checkbox" value="1" checked="checked" tabindex="<?php bbp_tab_index(); ?>" />
									<label for="bbp_topic_favoriters"><?php _e( 'Merge discussion favoriters', 'buddyboss' ); ?></label>
								</div>

								<?php if ( bbp_allow_topic_tags() ) : ?>
									<div class="bp-checkbox-wrap">
										<input name="bbp_topic_tags" id="bbp_topic_tags" class="bs-styled-checkbox" type="checkbox" value="1" checked="checked" tabindex="<?php bbp_tab_index(); ?>" />
										<label for="bbp_topic_tags"><?php _e( 'Merge discussion tags', 'buddyboss' ); ?></label>
									</div>
								<?php endif; ?>
							</div>
						</fieldset>

						<div class="bp-feedback error">
							<span class="bp-icon" aria-hidden="true"></span>
							<p><?php _e( '<strong>WARNING:</strong> This process cannot be undone.', 'buddyboss' ); ?></p>
						</div>

						<div class="bbp-submit-wrapper">
							<button type="submit" tabindex="<?php bbp_tab_index(); ?>" id="bbp_merge_topic_submit" name="bbp_merge_topic_submit" class="button submit"><?php _e( 'Submit', 'buddyboss' ); ?></button>
						</div>
					</div>

					<?php bbp_merge_topic_form_fields(); ?>

				</fieldset>
			</form>
		</div>

	<?php else : ?>

		<div id="no-topic-<?php bbp_topic_id(); ?>" class="bbp-no-topic">
			<div class="entry-content"><?php is_user_logged_in() ? _e( 'You do not have the permissions to edit this discussion!', 'buddyboss' ) : _e( 'You cannot edit this discussion.', 'buddyboss' ); ?></div>
		</div>

	<?php endif; ?>

</div>
