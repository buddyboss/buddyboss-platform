<?php global $messages_template; ?>

<?php if ( bp_has_message_threads( bp_ajax_querystring( 'messages' ) . '&user_id=' . get_current_user_id() ) ) : ?>
	<?php while ( bp_message_threads() ) : bp_message_thread(); ?>

        <?php $last_message_id = (int) $messages_template->thread->last_message_id; ?>

        <li class="read-item">
			<span class="bb-full-link">
				<a href="<?php bp_message_thread_view_link( bp_get_message_thread_id() ); ?>">
					<?php bp_message_thread_subject(); ?>
				</a>
			</span>
            <div class="notification-avatar">
                <a href="<?php echo bp_core_get_user_domain( $messages_template->thread->last_sender_id ); ?>">
					<?php bp_message_thread_avatar(); ?>
                </a>
            </div>
            <div class="notification-content">
				<span class="bb-full-link">
					<a href="<?php bp_message_thread_view_link( bp_get_message_thread_id() ); ?>">
						<?php bp_message_thread_subject(); ?>
					</a>
				</span>
                <span class="notification-users">
					<a href="<?php bp_message_thread_view_link( bp_get_message_thread_id() ); ?>">
						<?php $recipients = (array) $messages_template->thread->recipients; ?>
						<?php $recipient_names = array(); ?>

						<?php foreach ( $recipients as $recipient ) : ?>
							<?php if ( (int) $recipient->user_id !== bp_loggedin_user_id() ) : ?>
								<?php $recipient_name = bp_core_get_user_displayname( $recipient->user_id ); ?>

								<?php if ( empty( $recipient_name ) ) : ?>
									<?php $recipient_name = __( 'Deleted User', 'buddyboss-theme' ); ?>
								<?php endif; ?>

								<?php $recipient_names[] = $recipient_name; ?>
							<?php else : ?>
								<?php $recipient_names[] = __( 'you', 'buddyboss-theme' ); ?>
							<?php endif; ?>
						<?php endforeach; ?>

						<?php
							// Concatenate to natural language string.
							echo wp_sprintf_l( '%l', $recipient_names ); 
						?>
					</a>
				</span>
                <span class="posted">
                    <?php $exerpt = strip_tags( bp_create_excerpt( $messages_template->thread->last_message_content, 50, array( 'ending' => '&hellip;' ) ) ); ?>
                    
                    <?php if ( function_exists( 'buddypress' ) && bp_is_active( 'media' ) ) : ?>
                        <?php if ( bp_is_messages_media_support_enabled() ) : ?>
                            <?php $media_ids = bp_messages_get_meta( $last_message_id, 'bp_media_ids', true ); ?>

                            <?php if ( ! empty( $media_ids ) ) : ?>
                                <?php $media_ids = explode( ',', $media_ids ); ?>
                                
                                <?php if ( sizeof( $media_ids ) < 2 ) : ?>
	                                <?php $exerpt = __( 'sent a photo', 'buddyboss-theme' ); ?>
                                <?php else : ?>
	                                <?php $exerpt = __( 'sent some photos', 'buddyboss-theme' ); ?>
                                <?php endif; ?>
                           <?php endif; ?>
                        <?php endif; ?>

                        <?php if ( bp_is_messages_gif_support_enabled() ) : ?>
                            <?php $gif_data = bp_messages_get_meta( $last_message_id, '_gif_data', true ); ?>

                            <?php if ( ! empty( $gif_data ) ) : ?>
                                <?php $exerpt = __( 'sent a gif', 'buddyboss-theme' ); ?>
                            <?php endif; ?>
                        <?php endif; ?>
                    <?php endif; ?>

                    <?php echo bp_core_get_user_displayname( $messages_template->thread->last_sender_id ); ?>: <?php echo stripslashes_deep($exerpt); ?>
                </span>
            </div>
        </li>
	<?php endwhile; ?>
<?php else : ?>
    <li class="bs-item-wrap">
        <div class="notification-content"><?php _e( 'No new messages', 'buddyboss-theme' ); ?>!</div>
    </li>
 <?php endif; ?>