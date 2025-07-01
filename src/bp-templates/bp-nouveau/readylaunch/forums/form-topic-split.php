<?php
/**
 * Split Topic Form Template
 *
 * @package BuddyBoss\Template
 * @subpackage BP_Nouveau\ReadyLaunch
 * @since BuddyBoss 2.9.00
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;
?>

<div id="bbpress-forums">

	<?php if ( is_user_logged_in() && current_user_can( 'edit_topic', bbp_get_topic_id() ) ) : ?>

		<div id="split-topic-<?php bbp_topic_id(); ?>" class="bbp-topic-split bb-rl-forum-modal bb-rl-forum-modal--static">

			<form id="split_topic" class="bb-rl-forum-form" name="split_topic" method="post" action="<?php the_permalink(); ?>">

				<fieldset class="bbp-form">

				<div class="bb-rl-forum-modal-header">
					<h3>
						<?php
						/* translators: %s: Topic title */
						printf( esc_html__( 'Split discussion "%s"', 'buddyboss' ), bbp_get_topic_title() );
						?>
					</h3>
				</div>

					<div>

						<div class="bb-rl-forum-modal-content">
							<div class="bb-rl-forum-fieldset">
								<div class="bp-feedback info">
									<span class="bp-icon" aria-hidden="true"></span>
									<p><?php esc_html_e( 'When you split a discussion, you are slicing it in half starting with the reply you just selected. Choose to use that reply as a new discussion with a new title, or merge those replies into an existing discussion.', 'buddyboss' ); ?></p>
								</div>

								<div class="bp-feedback info">
									<span class="bp-icon" aria-hidden="true"></span>
									<p><?php esc_html_e( 'If you use the existing discussion option, replies within both discussions will be merged chronologically. The order of the merged replies is based on the time and date they were posted.', 'buddyboss' ); ?></p>
								</div>
							</div>

							<fieldset class="bbp-form bb-rl-forum-fieldset">
								<legend><?php esc_html_e( 'Split Method', 'buddyboss' ); ?></legend>

								<div class="bb-rl-forum-fieldset">
									<div class="bp-radio-wrap">
										<input name="bbp_topic_split_option" id="bbp_topic_split_option_reply" class="bs-styled-radio" type="radio" checked="checked" value="reply" tabindex="<?php bbp_tab_index(); ?>" />
										<label for="bbp_topic_split_option_reply"><?php printf( wp_kses_post( __( 'New discussion in <strong>%s</strong> titled:', 'buddyboss' ) ), bbp_get_forum_title( bbp_get_topic_forum_id( bbp_get_topic_id() ) ) ); ?></label>
									</div>
									<input type="text" id="bbp_topic_split_destination_title" value="<?php /* translators: %s: Topic title */ printf( esc_attr__( 'Split: %s', 'buddyboss' ), wp_kses_post( bbp_get_topic_title() ) ); ?>" tabindex="<?php bbp_tab_index(); ?>" size="35" name="bbp_topic_split_destination_title" />
								</div>

								<?php
								if ( bbp_has_topics(
									array(
										'show_stickies' => false,
										'post_parent'   => bbp_get_topic_forum_id( bbp_get_topic_id() ),
										'post__not_in'  => array( bbp_get_topic_id() ),
									)
								) ) :
									?>

									<div>
										<div class="bp-radio-wrap">
											<input name="bbp_topic_split_option" id="bbp_topic_split_option_existing" class="bs-styled-radio" type="radio" value="existing" tabindex="<?php bbp_tab_index(); ?>" />
											<label for="bbp_topic_split_option_existing"><?php esc_html_e( 'Use an existing discussion in this forum:', 'buddyboss' ); ?></label>
										</div>

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
									</div>

								<?php endif; ?>

							</fieldset>

							<fieldset class="bbp-form bb-rl-forum-fieldset">
								<legend><?php esc_html_e( 'Discussion Extras', 'buddyboss' ); ?></legend>

								<div>
									<?php if ( bb_is_enabled_subscription( 'topic' ) ) : ?>
										<div class="bp-checkbox-wrap">
											<input name="bbp_topic_subscribers" id="bbp_topic_subscribers" class="bs-styled-checkbox" type="checkbox" value="1" checked="checked" tabindex="<?php bbp_tab_index(); ?>" />
											<label for="bbp_topic_subscribers"><?php esc_html_e( 'Copy subscribers to the new discussion', 'buddyboss' ); ?></label>
										</div>
									<?php endif; ?>

									<div class="bp-checkbox-wrap">
										<input name="bbp_topic_favoriters" id="bbp_topic_favoriters" class="bs-styled-checkbox" type="checkbox" value="1" checked="checked" tabindex="<?php bbp_tab_index(); ?>" />
										<label for="bbp_topic_favoriters"><?php esc_html_e( 'Copy favoriters to the new discussion', 'buddyboss' ); ?></label>
									</div>

									<?php if ( bbp_allow_topic_tags() ) : ?>
										<div class="bp-checkbox-wrap">
											<input name="bbp_topic_tags" id="bbp_topic_tags" class="bs-styled-checkbox" type="checkbox" value="1" checked="checked" tabindex="<?php bbp_tab_index(); ?>" />
											<label for="bbp_topic_tags"><?php esc_html_e( 'Copy discussion tags to the new discussion', 'buddyboss' ); ?></label>
										</div>
									<?php endif; ?>
								</div>
							</fieldset>

							<div class="bp-feedback error">
								<span class="bp-icon" aria-hidden="true"></span>
								<p><?php wp_kses_post( __( '<strong>WARNING:</strong> This process cannot be undone.', 'buddyboss' ) ); ?></p>
							</div>

						</div>

						<div class="bb-rl-forum-modal-footer bb-rl-forum-modal-footer--static">
							<div class="bbp-submit-wrapper">
								<button type="submit" tabindex="<?php bbp_tab_index(); ?>" id="bbp_merge_topic_submit" name="bbp_merge_topic_submit" class="bb-rl-button bb-rl-button--brandFill bb-rl-button--small submit"><?php esc_html_e( 'Submit', 'buddyboss' ); ?></button>
							</div>
						</div>
					</div>

					<?php bbp_split_topic_form_fields(); ?>

				</fieldset>
			</form>
		</div>

	<?php else : ?>

		<div id="no-topic-<?php bbp_topic_id(); ?>" class="bbp-no-topic">
			<div class="entry-content"><?php is_user_logged_in() ? esc_html_e( 'You do not have the permissions to edit this discussion!', 'buddyboss' ) : esc_html_e( 'You cannot edit this discussion.', 'buddyboss' ); ?></div>
		</div>

	<?php endif; ?>

</div>
