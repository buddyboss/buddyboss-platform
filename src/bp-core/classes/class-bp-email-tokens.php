<?php
/**
 * Core component classes.
 *
 * @package BuddyBoss\Core
 *
 * @since BuddyBoss 1.0.0
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Represents an email tokens that will be sent in emails.
 *
 * @since BuddyBoss 1.0.0
 */
class BP_Email_Tokens {

	/**
	 * message sender id
	 *
	 * @since BuddyBoss 1.0.0
	 */
	protected $_message_sender_id = false;

	/**
	 * Constructor.
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function __construct() {
		// set new email tokens added in BuddyBoss 1.0.0
		add_filter( 'bp_email_set_tokens', array( $this, 'set_tokens' ), 10, 3 );

		// tokens for email after a new message is received, does not contain usable info about sender user
		// we need to acquire this info before we process tokens for that email
		// priority 9 is importent
		add_action( 'messages_message_sent', array( $this, 'messages_message_sent' ), 9 );
	}

	/**
	 * Set email tokens
	 *
	 * @param array     $formatted_tokens
	 * @param array     $tokens
	 * @param \BP_Email $bp_email
	 *
	 * @return array
	 */
	function set_tokens( $formatted_tokens, $tokens, $bp_email ) {
		if ( 'html' == $bp_email->get_content_type() ) {
			$email_content = $bp_email->get_content_html();

			$all_tokens = $this->get_tokens();
			if ( ! empty( $all_tokens ) ) {
				foreach ( $all_tokens as $token_key => $token_details ) {
					if ( strpos( $email_content, $token_key ) !== false ) {
						$token_output = call_user_func( $token_details['function'], $bp_email, $formatted_tokens, $tokens );
						$formatted_tokens[ sanitize_text_field( $token_key ) ] = $token_output;
					}
				}
			}
		}

		return $formatted_tokens;
	}

	/**
	 * Get email tokens
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @return array
	 */
	public function get_tokens() {

		$tokens = array(
			'group.small_card'     => array(
				'function'    => array( $this, 'token__group_card_small' ),
				'description' => __( 'Display the group card with minimal group details.', 'buddyboss' ),
			),
			'group.card'           => array(
				'function'    => array( $this, 'token__group_card' ),
				'description' => __( 'Display the group card with more details like group cover photo etc.', 'buddyboss' ),
			),
			'group.description'    => array(
				'function'    => array( $this, 'token__group_description' ),
				'description' => __( 'Display the group description.', 'buddyboss' ),
			),
			'group.invite_message' => array(
				'function'    => array( $this, 'token__group_invite_message' ),
				'description' => __( 'Display the invite message.', 'buddyboss' ),
			),
			'message'              => array(
				'function'    => array( $this, 'token__message' ),
				'description' => __( 'Display the sent message, along with sender\'s photo and name.', 'buddyboss' ),
			),
			'sender.url'           => array(
				'function'    => array( $this, 'token__sender_url' ),
				'description' => __( 'Display the link to the member profile who sent the message. Only works in email that is sent to a member when someone sends him/her a message.', 'buddyboss' ),
			),
			'member.card'          => array(
				'function'    => array( $this, 'token__member_card_small' ),
				'description' => __( 'Display the member card with minimal member details.', 'buddyboss' ),
			),
			'status_update'        => array(
				'function'    => array( $this, 'token__status_update' ),
				'description' => __( 'Display the status update, along with member\'s photo and name.', 'buddyboss' ),
			),
			'activity_reply'       => array(
				'function'    => array( $this, 'token__activity_reply' ),
				'description' => __( 'Display the reply to update, along with member\'s photo and name.', 'buddyboss' ),
			),
			'poster.url'           => array(
				'function'    => array( $this, 'token__poster_url' ),
				'description' => __( 'Display the link to the member profile who posted the update.', 'buddyboss' ),
			),
			'discussion.content'   => array(
				'function'    => array( $this, 'token__discussion_content' ),
				'description' => __( 'Display the discussion content.', 'buddyboss' ),
			),
			'reply.content'        => array(
				'function'    => array( $this, 'token__reply_content' ),
				'description' => __( 'Display the reply content.', 'buddyboss' ),
			),
		);

		return $tokens;
	}

	/**
	 * Generate the output for token group.small_card
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @param \BP_Email $bp_email
	 * @param array     $formatted_tokens
	 * @param array     $tokens
	 *
	 * @return string html for the output
	 */
	public function token__group_card_small( $bp_email, $formatted_tokens, $tokens ) {
		$output = '';

		$settings = bp_email_get_appearance_settings();

		$group = isset( $tokens['group'] ) ? $tokens['group'] : false;
		if ( empty( $group ) ) {
			$group_id = isset( $tokens['group.id'] ) ? $tokens['group.id'] : false;
			if ( empty( $group_id ) ) {
				return $output;
			}

			$group = groups_get_group( $group_id );
		}

		if ( empty( $group ) ) {
			return $output;
		}

		$group_visibility = $group->status;
		
		if ( 'public' === $group->status ) {
			$group_visibility = __( 'Public', 'buddyboss' );
		} elseif ( 'hidden' === $group->status ) {
			$group_visibility = __( 'Hidden', 'buddyboss' );
		} elseif ( 'private' === $group->status ) {
			$group_visibility = __( 'Private', 'buddyboss' );
		}

		ob_start();
		?>
		<table cellspacing="0" cellpadding="0" border="0" width="100%"
			   style="background: <?php echo esc_attr( $settings['body_bg'] ); ?>; border: 1px solid <?php echo esc_attr( $settings['body_border_color'] ); ?>; border-radius: 4px; border-collapse: separate !important">
			<tbody>
			<tr>
				<td height="16px" style="font-size: 16px; line-height: 16px;">&nbsp;</td>
			</tr>
			<tr>
				<td align="center">
					<table cellpadding="0" cellspacing="0" border="0" width="94%" style="width: 94%;">
						<tbody>
						<tr>
							<td>
								<table cellpadding="0" cellspacing="0" border="0">
									<tbody>
									<tr>
										<td width="20%" class="mobile-block-full">
											<a class="group-avatar-wrap mobile-center" href="<?php echo bp_get_group_permalink( $group ); ?>"
											   style="border: 1px solid <?php echo esc_attr( $settings['body_border_color'] ); ?>; display: block; border-radius: 3px; width: 104px;">
												<?php
												$group_avatar = bp_core_fetch_avatar(
													array(
														'item_id' => $group->id,
														'avatar_dir' => 'group-avatars',
														'type' => 'full',
														'object' => 'group',
														'width' => 200,
														'height' => 200,
														'html' => false,
													)
												);
												?>
												<img alt="" src="<?php echo $group_avatar; ?>" width="100" height="100" border="0"
													 style="margin: 2px; padding:0; box-sizing: border-box; border-radius: 3px; border: 3px solid <?php echo esc_attr( $settings['body_bg'] ); ?>; display:block;" />
											</a>
										</td>
										<td width="4%" class="mobile-hide">&nbsp;</td>
										<td width="76%" class="mobile-block-padding-full">
											<table cellpadding="0" cellspacing="0" border="0" width="100%">
												<tbody>
												<tr>
													<td class="mobile-text-center">
														<div style="font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: <?php echo esc_attr( floor( $settings['body_text_size'] * 1.625 ) . 'px' ); ?>; color: <?php echo esc_attr( $settings['body_secondary_text_color'] ); ?>; line-height: <?php echo esc_attr( floor( $settings['body_text_size'] * 1.625 ) . 'px' ); ?>;"><?php echo $group->name; ?></div>
														<div class="spacer" style="font-size: 3px; line-height: 3px; height: 3px;">&nbsp;</div>
														<p style="opacity: 0.7; font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: <?php echo esc_attr( floor( $settings['body_text_size'] * 0.8125 ) . 'px' ); ?>; color: <?php echo esc_attr( $settings['body_text_color'] ); ?>; margin: 0;"><?php echo $group_visibility . ' ' . __( 'Group', 'buddyboss' ); ?></p>
													</td>
												</tr>
												<tr>
													<td height="16px" style="font-size: 16px; line-height: 16px;">&nbsp;</td>
												</tr>
												<tr>
													<td>
														<table cellpadding="0" cellspacing="0" border="0" width="100%">
															<tbody>
															<tr>
																<td>
																	<table cellpadding="0" cellspacing="0" border="0" width="47%" style="width: 47%;" align="left" class="responsive-table mobile-text-center">
																		<tbody>
																			<tr>
																				<td height="34px" style="vertical-align: middle;">
																					<?php
																					$group_members_count = bp_get_group_total_members( $group );
																					$member_text         = ( $group_members_count > 1 ) ? __( 'members', 'buddyboss' ) : __( 'member', 'buddyboss' );
																					?>
																					<div style="font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;font-size: <?php echo esc_attr( floor( $settings['body_text_size'] * 0.8125 ) . 'px' ); ?>; color: <?php echo esc_attr( $settings['body_text_color'] ); ?>;">
																						<span style="color: <?php echo esc_attr( $settings['body_secondary_text_color'] ); ?>; opacity: 0.85;"><?php echo $group_members_count; ?></span> <?php echo $member_text; ?>
																					</div>
																				</td>
																			</tr>
																		</tbody>
																	</table>
																	<table cellpadding="0" cellspacing="0" border="0" width="47%" style="width: 47%;" align="right" class="responsive-table">
																		<tbody>
																			<tr>
																				<td height="34px" align="right" style="vertical-align: middle;" class="mobile-padding-bottom">
																					<a class="mobile-button-center" href="<?php echo bp_get_group_permalink( $group ); ?>" target="_blank" rel="nofollow" style="font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;font-size: <?php echo esc_attr( floor( $settings['body_text_size'] * 0.875 ) . 'px' ); ?>;text-decoration: none;display: block;border-radius: 100px;text-align: center; height: 16px;line-height: 16px;background: <?php echo $settings['highlight_color']; ?>;color: #fff !important;width: 110px;padding: 8px;"><font style="color:#fff;"><?php _e( 'Visit Group', 'buddyboss' ); ?></font></a>
																				</td>
																			</tr>
																		</tbody>
																	</table>
																</td>
															</tr>
															</tbody>
														</table>
													</td>
												</tr>
												</tbody>
											</table>
										</td>
									</tr>
									</tbody>
								</table>
							</td>
						</tr>
						</tbody>
					</table>
				</td>
			</tr>
			<tr>
				<td height="16px" style="font-size: 16px; line-height: 16px;">&nbsp;</td>
			</tr>
			</tbody>
		</table>
		<div class="spacer" style="font-size: 10px; line-height: 10px; height: 10px;">&nbsp;</div>
		<?php
		$output = str_replace( array( "\r", "\n" ), '', ob_get_clean() );

		return $output;
	}

	/**
	 * Generate the output for token group.card
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @param \BP_Email $bp_email
	 * @param array     $formatted_tokens
	 * @param array     $tokens
	 *
	 * @return string html for the output
	 */
	public function token__group_card( $bp_email, $formatted_tokens, $tokens ) {
		$output = '';

		$group = isset( $tokens['group'] ) ? $tokens['group'] : false;
		if ( empty( $group ) ) {
			$group_id = isset( $tokens['group.id'] ) ? $tokens['group.id'] : false;
			if ( empty( $group_id ) ) {
				return $output;
			}

			$group = groups_get_group( $group_id );
		}

		if ( empty( $group ) ) {
			return $output;
		}

		ob_start();
		?>
		<div class="card_wrapper card_group card_group_small" style="border-radius: 5px; padding: 10px;">
			<table cellspacing="0" cellpadding="0" border="0" width="100%">
				<?php
				if ( bp_is_active( 'groups', 'cover_image' ) && ! bp_disable_cover_image_uploads() && bp_attachments_is_wp_version_supported() ) {
					$cover_image = bp_attachments_get_attachment(
						'url',
						array(
							'object_dir' => 'groups',
							'item_id'    => $group_id,
						)
					);
					echo "<tr><td colspan='100%'><img src='{$cover_image}'></td></tr>";
				}
				?>

				<tr>
					<td>
						<a href="<?php echo bp_get_group_permalink( $group ); ?>"
						   style="border: 1px solid <?php echo esc_attr( $settings['body_border_color'] ); ?>; display: block; border-radius: 3px; width: 100px;">
							<img alt="" src="
							<?php
							echo bp_core_fetch_avatar(
								array(
									'item_id'    => $group->id,
									'avatar_dir' => 'group-avatars',
									'object'     => 'group',
									'type'       => 'full',
									'width'      => 200,
									'height'     => 200,
									'html'       => false,
								)
							);
							?>
							" width="100" height="100"
								 style="margin:0; padding:0; box-sizing: border-box; border-radius: 3px; border:3px solid <?php echo esc_attr( $settings['body_bg'] ); ?>; display:block;"
								 border="0"/>
						</a>
					</td>
					<td>
						<h3><?php echo $group->name; ?></h3>
						<div class="spacer" style="font-size: 7px; line-height: 7px; height: 7px;">&nbsp;</div>
						<?php echo ucfirst( $group->status ) . ' ' . __( 'Group', 'buddyboss' ); ?><br>
						<table cellspacing="0" cellpadding="0" border="0" width="100%">
							<tr>
								<td align="left">
									<?php
									if ( bp_is_active( 'activity' ) ) {

										global $wpdb;

										$activity_table = buddypress()->activity->table_name;

										$sql = array(
											'select'  => "SELECT user_id, max( date_recorded ) as date_recorded FROM {$activity_table}",
											'where'   => array(),
											'groupby' => 'GROUP BY user_id',
											'orderby' => 'ORDER BY date_recorded',
											'order'   => 'DESC',
										);

										$sql['where'] = array(
											'item_id = ' . absint( $group->id ),
											$wpdb->prepare( 'component = %s', buddypress()->groups->id ),
										);

										$sql['where'] = 'WHERE ' . implode( ' AND ', $sql['where'] );

										$sql['limit'] = 'LIMIT 4';

										$group_user_ids = $wpdb->get_results( "{$sql[ 'select' ]} {$sql[ 'where' ]} {$sql[ 'groupby' ]} {$sql[ 'orderby' ]} {$sql[ 'order' ]} {$sql[ 'limit' ]}" );

										$group_user_ids = wp_list_pluck( $group_user_ids, 'user_id' );

										$output = "<span class='bs-group-members'>";
										foreach ( $group_user_ids as $user_id ) {
											$avatar = bp_core_fetch_avatar(
												array(
													'item_id' => $user_id,
													'avatar_dir' => 'avatars',
													'object' => 'user',
													'type' => 'full',
													'html' => false,
												)
											);

											if ( ! empty( $avatar ) ) {
												$output .= sprintf( "<img src='%s' alt='%s'>", $avatar, bp_core_get_user_displayname( $user_id ) );
											}
										}
										$output .= '</span>';

										$output .= "<span class='members'>" . groups_get_total_member_count( $group->id ) . ' ' . __( 'Members', 'buddyboss' ) . '</span>';
										echo $output;
									}
									?>
								</td>

								<td align="right">
									<a class="button-primary" href="<?php echo bp_get_group_permalink( $group ); ?>">
										<?php
										$joined_status = 'unknown';
										$recepients    = $bp_email->get_to();
										if ( ! empty( $recepients ) && count( $recepients ) === 1 ) {
											// BP_Email_Recipient
											$recepient = reset( $recepients );
											$user      = $recepient->get_user();
											if ( ! empty( $user ) ) {
												if ( groups_is_user_member( $user->ID, $group->id ) ) {
													$joined_status = 'joined';
												} else {
													$joined_status = 'not-joined';
												}
											}
										}

										switch ( $joined_status ) {
											case 'joined':
												_e( 'Leave Group', 'buddyboss' );
												break;
											case 'not-joined':
												_e( 'Join Group', 'buddyboss' );
												break;
											default:
												_e( 'Visit Group', 'buddyboss' );
												break;
										}
										?>
									</a>
								</td>
							</tr>
						</table>
					</td>
				</tr>
			</table>
			<div class="spacer" style="font-size: 10px; line-height: 10px; height: 10px;">&nbsp;</div>
		</div>

		<?php
		$output = str_replace( array( "\r", "\n" ), '', ob_get_clean() );

		return $output;
	}

	/**
	 * Generate the output for token status_update
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @param \BP_Email $bp_email
	 * @param array     $formatted_tokens
	 * @param array     $tokens
	 *
	 * @return string html for the output
	 */
	public function token__status_update( $bp_email, $formatted_tokens, $tokens ) {
		$output = '';

		$settings = bp_email_get_appearance_settings();

		$activity = isset( $tokens['activity'] ) ? $tokens['activity'] : false;

		if ( empty( $activity ) ) {
			return $output;
		}

		ob_start();
		?>
		<table cellspacing="0" cellpadding="0" border="0" width="100%">
			<tr>
				<td align="center">
					<table cellpadding="0" cellspacing="0" border="0" width="100%">
						<tbody>
						<tr>
							<td valign="middle" width="65px" style="vertical-align: middle;">
								<a style="display: block; width: 47px;" href="<?php echo esc_attr( bp_core_get_user_domain( $activity->user_id ) ); ?>"
								   target="_blank" rel="nofollow">
									<?php
									$avatar_url = bp_core_fetch_avatar(
										array(
											'item_id' => $activity->user_id,
											'width'   => 100,
											'height'  => 100,
											'type'    => 'full',
											'html'    => false,
										)
									);
									?>
									<img src="<?php echo esc_attr( $avatar_url ); ?>" width="47" height="47"
										 style="margin:0; padding:0; border:none; display:block; max-width: 47px; border-radius: 50%;"
										 border="0">
								</a>
							</td>
							<td width="88%" style="vertical-align: middle;">
								<div style="color: <?php echo esc_attr( $settings['body_secondary_text_color'] ); ?>; font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: <?php echo esc_attr( $settings['body_text_size'] . 'px' ); ?>; line-height: <?php echo esc_attr( $settings['body_text_size'] . 'px' ); ?>; letter-spacing: -0.24px;"><?php echo bp_core_get_user_displayname( $activity->user_id ); ?></div>
							</td>
						</tr>
						</tbody>
					</table>
				</td>
			</tr>

			<tr>
				<td height="24px" style="font-size: 24px; line-height: 24px;">&nbsp;</td>
			</tr>

			<tr>
				<td>
					<table cellspacing="0" cellpadding="0" border="0" width="100%"
						   style="background: <?php echo esc_attr( $settings['quote_bg'] ); ?>; border: 1px solid <?php echo esc_attr( $settings['body_border_color'] ); ?>; border-radius: 4px; border-collapse: separate !important">
						<tbody>
						<tr>
							<td height="5px" style="font-size: 5px; line-height: 5px;">&nbsp;</td>
						</tr>
						<tr>
							<td align="center">
								<table cellpadding="0" cellspacing="0" border="0" width="88%" style="width: 88%;">
									<tbody>
										<tr>
											<td>
												<div style="color: <?php echo esc_attr( $settings['body_text_color'] ); ?>; font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: <?php echo esc_attr( $settings['body_text_size'] . 'px' ); ?>; letter-spacing: -0.24px; line-height: <?php echo esc_attr( floor( $settings['body_text_size'] * 1.625 ) . 'px' ); ?>;">
													<?php
													echo apply_filters_ref_array(
														'bp_get_activity_content_body',
														array(
															$activity->content,
															&$activity,
														)
													);
													?>
												</div>
											</td>
										</tr>
									</tbody>
								</table>
							</td>
						</tr>
						<tr>
							<td height="5px" style="font-size: 5px; line-height: 5px;">&nbsp;</td>
						</tr>
						</tbody>
					</table>
				</td>
			</tr>

			<tr>
				<td height="24px" style="font-size: 24px; line-height: 24px;">&nbsp;</td>
			</tr>

			<tr>
				<td>
					<a href="<?php echo esc_attr( $tokens['mentioned.url'] ); ?>" target="_blank" rel="nofollow" style="font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: 14px; color: <?php echo $settings['highlight_color']; ?>; text-decoration: none; display: inline-block; border: 1px solid <?php echo $settings['highlight_color']; ?>; border-radius: 100px;  min-width: 64px; text-align: center; height: 16px; line-height: 16px; padding:8px;"><?php _e( 'Reply', 'buddyboss' ); ?></a>
				</td>
			</tr>
		</table>
		<div class="spacer" style="font-size: 20px; line-height: 20px; height: 20px;">&nbsp;</div>
		<?php
		$output = str_replace( array( "\r", "\n" ), '', ob_get_clean() );

		return $output;
	}

	/**
	 * Generate the output for token activity_reply
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @param \BP_Email $bp_email
	 * @param array     $formatted_tokens
	 * @param array     $tokens
	 *
	 * @return string html for the output
	 */
	public function token__activity_reply( $bp_email, $formatted_tokens, $tokens ) {
		$output = '';

		$settings = bp_email_get_appearance_settings();

		$comment_id       = isset( $tokens['comment.id'] ) ? $tokens['comment.id'] : false;
		$activity_comment = new BP_Activity_Activity( $comment_id );
		if ( empty( $activity_comment ) || empty( $activity_comment->secondary_item_id ) ) {
			return $output;
		}

		$activity_original_id = ! empty( $activity_comment->item_id ) ? $activity_comment->item_id : $activity_comment->secondary_item_id;
		$activity_original    = new BP_Activity_Activity( $activity_original_id );
		if ( empty( $activity_original ) ) {
			return $output;
		}

		ob_start();
		?>
		<table cellspacing="0" cellpadding="0" border="0" width="100%">
			<tr>
				<td align="center">
					<table cellpadding="0" cellspacing="0" border="0" width="100%">
						<tbody>
						<tr>
							<td valign="middle" width="65px" style="vertical-align: middle;">
								<a style="display: block; width: 47px;" href="<?php echo esc_attr( bp_core_get_user_domain( $activity_comment->user_id ) ); ?>"
								   target="_blank" rel="nofollow">
									<?php
									$avatar_url = bp_core_fetch_avatar(
										array(
											'item_id' => $activity_comment->user_id,
											'width'   => 100,
											'height'  => 100,
											'type'    => 'full',
											'html'    => false,
										)
									);
									?>
									<img src="<?php echo esc_attr( $avatar_url ); ?>" width="47" height="47" border="0"
										 style="margin:0; padding:0; border:none; display:block; max-width: 47px; border-radius: 50%;" />
								</a>
							</td>
							<td width="88%" style="vertical-align: middle;">
								<div style="color: <?php echo esc_attr( $settings['body_secondary_text_color'] ); ?>; font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: <?php echo esc_attr( $settings['body_text_size'] . 'px' ); ?>; line-height: <?php echo esc_attr( $settings['body_text_size'] . 'px' ); ?>; letter-spacing: -0.24px;"><?php echo bp_core_get_user_displayname( $activity_comment->user_id ); ?></div>
							</td>
						</tr>
						</tbody>
					</table>
				</td>
			</tr>

			<tr>
				<td height="24px" style="font-size: 24px; line-height: 24px;">&nbsp;</td>
			</tr>

			<tr>
				<td>
					<table cellspacing="0" cellpadding="0" border="0" width="100%"
						   style="background: <?php echo esc_attr( $settings['quote_bg'] ); ?>; border: 1px solid <?php echo esc_attr( $settings['body_border_color'] ); ?>; border-radius: 4px; border-collapse: separate !important">
						<tbody>
							<tr>
								<td height="5px" style="font-size: 5px; line-height: 5px;">&nbsp;</td>
							</tr>
							<tr>
								<td align="center">
									<table cellpadding="0" cellspacing="0" border="0" width="88%" style="width: 88%;">
										<tbody>
											<tr>
												<td>
													<div class="bb-content-body" style="font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: <?php echo esc_attr( $settings['body_text_size'] . 'px' ); ?>; letter-spacing: -0.24px; line-height: <?php echo esc_attr( floor( $settings['body_text_size'] * 1.625 ) . 'px' ); ?>;">
														<?php
														/**
														 * Display text before activity comment.
														 * 
														 * @since BuddyBoss 1.4.7
														 *
														 * @param object $activity_comment BP_Activity_Activity object,
														 * 
														 */
														do_action( 'bp_activity_before_email_content', $activity_comment );

														if ( in_array( $activity_comment->content, array( '&nbsp;', '&#8203;' ) ) ) {
															$activity_comment->content = '';
														}
														echo apply_filters_ref_array( 'bp_get_activity_content_body', array( $activity_comment->content, &$activity_comment ) );

                                                        /**
														 * Display text after activity comment.
														 * 
														 * @since BuddyBoss 1.4.7
														 *
														 * @param object $activity_comment BP_Activity_Activity object,
														 * 
														 */
														do_action( 'bp_activity_after_email_content', $activity_comment );
														?>
													</div>
												</td>
											</tr>
										</tbody>
									</table>
								</td>
							</tr>
							<tr>
								<td height="5px" style="font-size: 5px; line-height: 5px;">&nbsp;</td>
							</tr>
						</tbody>
					</table>
				</td>
			</tr>

			<tr>
				<td height="24px" style="font-size: 24px; line-height: 24px;">&nbsp;</td>
			</tr>

			<tr>
				<td><a href="<?php echo esc_attr( $tokens['thread.url'] ); ?>" target="_blank" rel="nofollow" style="font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: 14px; color: <?php echo $settings['highlight_color']; ?>; text-decoration: none; display: inline-block; border: 1px solid <?php echo $settings['highlight_color']; ?>; border-radius: 100px; min-width: 64px; text-align: center; height: 16px; line-height: 16px; padding: 8px;"><?php _e( 'Reply', 'buddyboss' ); ?></a>
				</td>
			</tr>
		</table>
		<div class="spacer" style="font-size: 10px; line-height: 10px; height: 10px;">&nbsp;</div>
		<?php
		$output = str_replace( array( "\r", "\n" ), '', ob_get_clean() );

		return $output;
	}

	/**
	 * set message sender id
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @param \BP_Messages_Message $message
	 */
	public function messages_message_sent( $message ) {
		$this->_message_sender_id = $message->sender_id;
	}

	/**
	 * Generate the output for token message
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @param \BP_Email $bp_email
	 * @param array     $formatted_tokens
	 * @param array     $tokens
	 *
	 * @return string html for the output
	 */
	public function token__message( $bp_email, $formatted_tokens, $tokens ) {
		$output = '';

		$allow_type = array(
			'group-message-email',
			'messages-unread'
		);

		if ( ! in_array( $bp_email->get( 'type' ), $allow_type ) ) {
			return $output;
		}

		$settings = bp_email_get_appearance_settings();

		$media_ids       = false;
		$total_media_ids = 0;
		if ( bp_is_active( 'media' ) && bp_is_messages_media_support_enabled() && ! empty( $tokens['message_id'] ) ) {
			$media_ids = bp_messages_get_meta( $tokens['message_id'], 'bp_media_ids', true );

			if ( ! empty( $media_ids ) ) {
				$media_ids       = explode( ',', $media_ids );
				$total_media_ids = count( $media_ids );
				$media_ids       = implode( ',', array_slice( $media_ids, 0, 5 ) );
			}
		}

		$gif_data = false;
		if ( bp_is_active( 'media' ) && bp_is_messages_gif_support_enabled() && ! empty( $tokens['message_id'] ) ) {
			$gif_data = bp_messages_get_meta( $tokens['message_id'], '_gif_data', true );
		}

		ob_start();
		?>
		<table cellspacing="0" cellpadding="0" border="0" width="100%">
			<?php if ( $this->_message_sender_id ) : ?>
				<tr>
					<td>
						<table cellpadding="0" cellspacing="0" border="0" width="100%" style="width: 100%">
							<tbody>
								<tr>
									<td valign="middle" width="65px" style="vertical-align: middle;">
										<a style="display: block; width: 47px;" href="<?php echo esc_attr( bp_core_get_user_domain( $this->_message_sender_id ) ); ?>"
										   target="_blank" rel="nofollow">
											<?php
											$avatar_url = bp_core_fetch_avatar(
												array(
													'item_id' => $this->_message_sender_id,
													'width' => 100,
													'height' => 100,
													'type' => 'full',
													'html' => false,
												)
											);
											?>
											<img src="<?php echo esc_attr( $avatar_url ); ?>" width="47" height="47" border="0" style="margin:0; padding:0; border:none; display:block; max-width: 47px; border-radius: 50%;" />
										</a>
									</td>
									<td width="88%" style="vertical-align: middle;">
										<div style="font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: <?php echo esc_attr( $settings['body_text_size'] . 'px' ); ?>; line-height: <?php echo esc_attr( $settings['body_text_size'] . 'px' ); ?>; letter-spacing: -0.24px;">
											<a href="<?php echo esc_attr( bp_core_get_user_domain( $this->_message_sender_id ) ); ?>" target="_blank" rel="nofollow"
											   style="color: <?php echo esc_attr( $settings['body_secondary_text_color'] ); ?> !important;">
												<?php echo $tokens['sender.name']; ?>
											</a>
										</div>
									</td>
								</tr>
							</tbody>
						</table>
					</td>
				</tr>

				<tr>
					<td height="24px" style="font-size: 24px; line-height: 24px;">&nbsp;</td>
				</tr>
			<?php endif; ?>

			<tr>
				<td>
					<table cellspacing="0" cellpadding="0" border="0" width="100%"
						   style="background: <?php echo esc_attr( $settings['quote_bg'] ); ?>; border: 1px solid <?php echo esc_attr( $settings['body_border_color'] ); ?>; border-radius: 4px; border-collapse: separate !important">
						<tbody>
							<tr>
								<td height="25px" style="font-size: 25px; line-height: 25px;">&nbsp;</td>
							</tr>
							<tr>
								<td align="center">
									<table cellpadding="0" cellspacing="0" border="0" width="86%" style="width: 86%;">
										<tbody>
											<tr>
												<td>
													<div style="font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: <?php echo esc_attr( $settings['body_text_size'] . 'px' ); ?>; letter-spacing: -0.24px; line-height: <?php echo esc_attr( floor( $settings['body_text_size'] * 1.625 ) . 'px' ); ?>;">
														<?php echo nl2br( $tokens['usermessage'] ); ?>
													</div>
													<?php
													if ( ! empty( $media_ids ) && bp_has_media(
														array(
															'include' => $media_ids,
															'order_by' => 'menu_order',
															'sort' => 'ASC',
														)
													) ) :
														?>
														<div class="bb-activity-media-wrap" style="padding: 10px 0;">
															<?php
															while ( bp_media() ) {
																bp_the_media();
																?>
																<div class="bb-activity-media-elem"  style="display: inline-block; max-width: 120px; vertical-align: top; max-height: 120px; overflow: hidden; padding: 4px 0;">
																	<a href="<?php echo esc_attr( $tokens['message.url'] ); ?>">
																		<img src="<?php echo esc_attr( wp_get_attachment_image_url( bp_get_media_attachment_id() ) ); ?>" alt="<?php echo esc_attr( bp_get_media_title() ); ?>"/>
																	</a>
																</div>
																<?php
															}
															?>
															<?php if ( $total_media_ids > 5 ) : ?>
																<a href="<?php echo esc_attr( $tokens['message.url'] ); ?>"><?php sprintf( __( 'and %d more', 'buddyboss' ), $total_media_ids - 5 ); ?></a>
															<?php endif; ?>
														</div>
													<?php endif; ?>
													<?php if ( ! empty( $gif_data ) ) : ?>
														<div class="activity-attached-gif-container">
															<div class="gif-image-container">
																<a href="<?php echo esc_attr( $tokens['message.url'] ); ?>" class="gif-play-button">
																	<span class="bb-icon-play-thin"></span>
																	<?php if( is_int( $gif_data['still'] ) ) { ?>
																		<img src="<?php echo esc_url( wp_get_attachment_url( $gif_data['still'] ) ); ?>" />
																	<?php } else { ?>
																		<img src="<?php echo esc_url( $gif_data['still'] ); ?>" />
																	<?php } ?>
																</a>
																<span class="gif-icon"></span>
															</div>
														</div>
													<?php endif; ?>
												</td>
											</tr>
										</tbody>
									</table>
								</td>
							</tr>
							<tr>
								<td height="25px" style="font-size: 25px; line-height: 25px;">&nbsp;</td>
							</tr>
						</tbody>
					</table>
				</td>
			</tr>

			<tr>
				<td height="24px" style="font-size: 24px; line-height: 24px;">&nbsp;</td>
			</tr>

			<tr>
				<td>
					<a href="<?php echo esc_attr( $tokens['message.url'] ); ?>" target="_blank" rel="nofollow"
					   style="font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: 14px; color: <?php echo $settings['highlight_color']; ?>; text-decoration: none; display: inline-block; border: 1px solid <?php echo $settings['highlight_color']; ?>; border-radius: 100px; min-width: 64px; text-align: center; height: 16px; line-height: 16px; padding: 8px;"><?php _e( 'Reply', 'buddyboss' ); ?></a>
				</td>
			</tr>
		</table>
		<div class="spacer" style="font-size: 10px; line-height: 10px; height: 10px;">&nbsp;</div>
		<?php
		$output = str_replace( array( "\r", "\n" ), '', ob_get_clean() );

		return $output;
	}

	/**
	 * Generate the output for token member.card
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @param \BP_Email $bp_email
	 * @param array     $formatted_tokens
	 * @param array     $tokens
	 *
	 * @return string html for the output
	 */
	public function token__member_card_small( $bp_email, $formatted_tokens, $tokens ) {
		$output = '';

		$settings = bp_email_get_appearance_settings();

		$email_type = $bp_email->get( 'type' );
		switch ( $email_type ) {
			case 'friends-request-accepted':
				$member_id = isset( $tokens['friend.id'] ) ? $tokens['friend.id'] : false;
				break;
			case 'groups-membership-request':
				$member_id = isset( $tokens['requesting-user.id'] ) ? $tokens['requesting-user.id'] : false;
				break;
			case 'friends-request':
				$member_id = isset( $tokens['initiator.id'] ) ? $tokens['initiator.id'] : false;
				break;
			case 'groups-invitation':
				$member_id = isset( $tokens['inviter.id'] ) ? $tokens['inviter.id'] : false;
				break;
		}

		// maybe search for some other token

		if ( empty( $member_id ) ) {
			return $output;
		}

		ob_start();
		?>
		<table class="member-details" cellspacing="0" cellpadding="0" border="0" width="100%"
			   style="background: <?php echo esc_attr( $settings['body_bg'] ); ?>; border: 1px solid <?php echo esc_attr( $settings['body_border_color'] ); ?>; border-radius: 4px; border-collapse: separate !important">
			<tr>
				<td align="center">
					<table cellpadding="0" cellspacing="0" border="0" width="100%" style="width: 100%;">
						<tr>
							<td>
								<table cellpadding="0" cellspacing="0" border="0">
									<tr>
										<td width="20%" class="mobile-block-full">
											<a class="avatar-wrap mobile-center" href="<?php echo bp_core_get_user_domain( $member_id ); ?>"
											   style="display: block; border-radius: 3px; width: 140px;">
												<img alt="" src="
												<?php
												echo bp_core_fetch_avatar(
													array(
														'item_id' => $member_id,
														'width' => 280,
														'height' => 280,
														'type' => 'full',
														'html' => false,
													)
												);
												?>
												" width="140" height="140"
													 style="margin:0; padding:0; border:none; display:block;"
													 border="0"/>
											</a>
										</td>
										<td width="4%" class="mobile-hide">&nbsp;</td>
										<td width="72%" class="mobile-block-padding-full">
											<table cellpadding="0" cellspacing="0" border="0" width="100%"
												   style="width: 100%;">
												<tr>
													<td height="10px" style="font-size: 10px; line-height: 10px;">&nbsp;</td>
												</tr>
												<tr>
													<td class="mobile-text-center">
														<div style="font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: <?php echo esc_attr( floor( $settings['body_text_size'] * 1.25 ) . 'px' ); ?>; color: <?php echo esc_attr( $settings['body_secondary_text_color'] ); ?>; line-height: <?php echo esc_attr( floor( $settings['body_text_size'] * 1.75 ) . 'px' ); ?>;"><?php echo bp_core_get_user_displayname( $member_id ); ?></div>
														<div class="spacer" style="font-size: 2px; line-height: 2px; height: 2px;">&nbsp;</div>
														<p style="opacity: 0.7; font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: <?php echo esc_attr( floor( $settings['body_text_size'] * 0.8125 ) . 'px' ); ?>; color : <?php echo esc_attr( $settings['body_text_color'] ); ?>; margin: 0;">
															@<?php echo bp_activity_get_user_mentionname( $member_id ); ?>
														</p>
													</td>
												</tr>
												<tr>
													<td class="responsive-set-height">
														<div class="spacer responsive-set-height" style="font-size: 30px; line-height: 30px; height: 30px;">&nbsp;</div>
													</td>
												</tr>
												<tr>
													<td>
														<table cellpadding="0" cellspacing="0" border="0" width="100%" style="width: 100%;">
															<tr>
																<td>
																	<table cellpadding="0" cellspacing="0" border="0" width="47%" style="width: 47%;" align="left" class="no-responsive-table">
																		<tr>
																			<td height="34px" style="vertical-align: middle;">
																				<?php
																				$friend_count    = function_exists( 'friends_get_total_friend_count' ) ? friends_get_total_friend_count( $member_id ) : 0;
																				$connection_text = ( $friend_count > 1 ) ? __( 'connections', 'buddyboss' ) : __( 'connection', 'buddyboss' );
																				?>
																				<div style="font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;font-size: <?php echo esc_attr( floor( $settings['body_text_size'] * 0.8125 ) . 'px' ); ?>; color: <?php echo esc_attr( $settings['body_text_color'] ); ?>;">
																					<span style="color: <?php echo esc_attr( $settings['body_secondary_text_color'] ); ?>; opacity: 0.85;"><?php echo $friend_count; ?></span> <?php echo $connection_text; ?>
																				</div>
																			</td>
																		</tr>
																	</table>
																	<table cellpadding="0" cellspacing="0" border="0" width="47%" style="width: 47%;" align="right" class="no-responsive-table mobile-padding-bottom">
																		<tr>
																			<td height="34px" align="right" style="vertical-align: middle;" class="">
																				<a href="<?php echo bp_core_get_user_domain( $member_id ); ?>" target="_blank" rel="nofollow" style="font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;font-size: <?php echo esc_attr( floor( $settings['body_text_size'] * 0.875 ) . 'px' ); ?>;text-decoration: none;display: block;height: <?php echo esc_attr( floor( $settings['body_text_size'] * 2.125 ) . 'px' ); ?>;line-height: <?php echo esc_attr( floor( $settings['body_text_size'] * 2 ) . 'px' ); ?>;"><?php _e( 'View Profile', 'buddyboss' ); ?></a>
																			</td>
																		</tr>
																	</table>
																</td>
															</tr>
														</table>
													</td>
												</tr>
											</table>
										</td>
										<td width="4%" class="mobile-hide">&nbsp;</td>
									</tr>
								</table>
							</td>
						</tr>
					</table>
				</td>
			</tr>
		</table>
		<div class="spacer" style="font-size: 10px; line-height: 10px; height: 10px;">&nbsp;</div>
		<?php
		$output = str_replace( array( "\r", "\n" ), '', ob_get_clean() );

		return $output;
	}

	/**
	 * Generate the output for token poster.url
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @param \BP_Email $bp_email
	 * @param array     $formatted_tokens
	 * @param array     $tokens
	 *
	 * @return string html for the output
	 */
	public function token__poster_url( $bp_email, $formatted_tokens, $tokens ) {
		$activity = isset( $tokens['activity'] ) ? $tokens['activity'] : false;

		if ( empty( $activity ) ) {
			$user_id = isset( $tokens['commenter.id'] ) ? $tokens['commenter.id'] : false;
		} else {
			$user_id = $activity->user_id;
		}

		if ( empty( $user_id ) ) {
			return '';
		}

		return bp_core_get_user_domain( $user_id );
	}

	/**
	 * Generate the output for token sender.url
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @param \BP_Email $bp_email
	 * @param array     $formatted_tokens
	 * @param array     $tokens
	 *
	 * @return string html for the output
	 */
	public function token__sender_url( $bp_email, $formatted_tokens, $tokens ) {
		if ( empty( $this->_message_sender_id ) ) {
			return '';
		}

		return bp_core_get_user_domain( $this->_message_sender_id );
	}

	/**
	 * Generate the output for token group.description
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @param \BP_Email $bp_email
	 * @param array     $formatted_tokens
	 * @param array     $tokens
	 *
	 * @return string html for the output
	 */
	public function token__group_description( $bp_email, $formatted_tokens, $tokens ) {
		$group_id = false;
		$output   = '';

		if ( ! bp_is_active( 'groups' ) ) {
			return '';
		}

		$group = isset( $tokens['group'] ) ? $tokens['group'] : false;
		if ( empty( $group ) ) {
			$group_id = isset( $tokens['group.id'] ) ? $tokens['group.id'] : false;
			if ( empty( $group_id ) ) {
				return $output;
			}

			$group = groups_get_group( $group_id );
		}

		$group_excerpt = bp_get_group_description_excerpt( $group );

		if ( empty( $group ) || empty( $group_excerpt ) ) {
			return $output;
		}

		$settings = bp_email_get_appearance_settings();

		ob_start();
		?>
		<div class="spacer" style="font-size: 5px; line-height: 5px; height: 5px;">&nbsp;</div>
		<table cellspacing="0" cellpadding="0" border="0" width="100%"
				style="background: <?php echo esc_attr( $settings['quote_bg'] ); ?>; border: 1px solid <?php echo esc_attr( $settings['body_border_color'] ); ?>; border-radius: 4px; border-collapse: separate !important">
			 <tbody>
				<tr>
					<td height="5px" style="font-size: 5px; line-height: 5px;">&nbsp;</td>
				</tr>
				<tr>
					<td align="center">
						<table cellpadding="0" cellspacing="0" border="0" width="88%" style="width: 88%;">
							<tbody>
								<tr>
									<td>
										<div style="color: <?php echo esc_attr( $settings['body_text_color'] ); ?>; font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: <?php echo esc_attr( $settings['body_text_size'] . 'px' ); ?>; letter-spacing: -0.24px; line-height: <?php echo esc_attr( floor( $settings['body_text_size'] * 1.625 ) . 'px' ); ?>;">
											<?php echo wpautop( $group_excerpt ); ?>
										</div>
									</td>
								</tr>
							</tbody>
						</table>
					</td>
				</tr>
				<tr>
					<td height="5px" style="font-size: 5px; line-height: 5px;">&nbsp;</td>
				</tr>
			 </tbody>
		</table>
		<div class="spacer" style="font-size: 30px; line-height: 30px; height: 30px;">&nbsp;</div>
		<?php
		$output = str_replace( array( "\r", "\n" ), '', ob_get_clean() );

		return $output;
	}

	/**
	 * Generate the output for token group.invite_message
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @param \BP_Email $bp_email
	 * @param array     $formatted_tokens
	 * @param array     $tokens
	 *
	 * @return string html for the output
	 */
	public function token__group_invite_message( $bp_email, $formatted_tokens, $tokens ) {
		$output = '';

		if ( ! bp_is_active( 'groups' ) ) {
			return '';
		}

		if ( empty( $tokens['invite.message'] ) ) {
			return $output;
		}

		$settings = bp_email_get_appearance_settings();

		ob_start();
		?>
		<div class="spacer" style="font-size: 5px; line-height: 5px; height: 5px;">&nbsp;</div>
		<table cellspacing="0" cellpadding="0" border="0" width="100%"
			   style="background: <?php echo esc_attr( $settings['quote_bg'] ); ?>; border: 1px solid <?php echo esc_attr( $settings['body_border_color'] ); ?>; border-radius: 4px; border-collapse: separate !important">
			<tbody>
			<tr>
				<td height="5px" style="font-size: 5px; line-height: 5px;">&nbsp;</td>
			</tr>
			<tr>
				<td align="center">
					<table cellpadding="0" cellspacing="0" border="0" width="88%" style="width: 88%;">
						<tbody>
						<tr>
							<td>
								<div style="color: <?php echo esc_attr( $settings['body_text_color'] ); ?>; font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: <?php echo esc_attr( $settings['body_text_size'] . 'px' ); ?>; letter-spacing: -0.24px; line-height: <?php echo esc_attr( floor( $settings['body_text_size'] * 1.625 ) . 'px' ); ?>;">
									<?php echo wpautop( $tokens['invite.message'] ); ?>
								</div>
							</td>
						</tr>
						</tbody>
					</table>
				</td>
			</tr>
			<tr>
				<td height="5px" style="font-size: 5px; line-height: 5px;">&nbsp;</td>
			</tr>
			</tbody>
		</table>
		<div class="spacer" style="font-size: 30px; line-height: 30px; height: 30px;">&nbsp;</div>
		<?php
		$output = str_replace( array( "\r", "\n" ), '', ob_get_clean() );

		return $output;
	}

	/**
	 * Generate the output for token reply_content
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @param \BP_Email $bp_email
	 * @param array     $formatted_tokens
	 * @param array     $tokens
	 *
	 * @return string html for the output
	 */
	public function token__reply_content( $bp_email, $formatted_tokens, $tokens ) {
		$output = '';

		if ( empty( $formatted_tokens['reply.content'] ) || empty( $formatted_tokens['reply.id'] ) ) {
			return $output;
		}

		$settings = bp_email_get_appearance_settings();

		ob_start();
		?>
		<div class="spacer" style="font-size: 5px; line-height: 5px; height: 5px;">&nbsp;</div>
		<table cellspacing="0" cellpadding="0" border="0" width="100%">
			<tr>
				<td align="center">
					<table cellpadding="0" cellspacing="0" border="0" width="100%">
						<tbody>
						<tr>
							<td valign="middle" width="65px" style="vertical-align: middle;">
								<a style="display: block; width: 47px;" href="<?php echo esc_attr( bp_core_get_user_domain( bbp_get_reply_author_id( $formatted_tokens['reply.id'] ) ) ); ?>"
								   target="_blank" rel="nofollow">
									<?php
									$avatar_url = bp_core_fetch_avatar(
										array(
											'item_id' => bbp_get_reply_author_id( $formatted_tokens['reply.id'] ),
											'width'   => 100,
											'height'  => 100,
											'type'    => 'full',
											'html'    => false,
										)
									);
									?>
									<img src="<?php echo esc_attr( $avatar_url ); ?>" width="47" height="47" border="0"
										 style="margin:0; padding:0; border:none; display:block; max-width: 47px; border-radius: 50%;" />
								</a>
							</td>
							<td width="88%" style="vertical-align: middle;">
								<div style="color: <?php echo esc_attr( $settings['body_secondary_text_color'] ); ?>; font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: <?php echo esc_attr( $settings['body_text_size'] . 'px' ); ?>; line-height: <?php echo esc_attr( $settings['body_text_size'] . 'px' ); ?>; letter-spacing: -0.24px;"><?php echo bbp_get_reply_author_display_name( $formatted_tokens['reply.id'] ); ?></div>
							</td>
						</tr>
						</tbody>
					</table>
				</td>
			</tr>

			<tr>
				<td height="24px" style="font-size: 24px; line-height: 24px;">&nbsp;</td>
			</tr>

			<tr>
				<td>
					<table cellspacing="0" cellpadding="0" border="0" width="100%"
							style="background: <?php echo esc_attr( $settings['quote_bg'] ); ?>; border: 1px solid <?php echo esc_attr( $settings['body_border_color'] ); ?>; border-radius: 4px; border-collapse: separate !important">
						 <tbody>
							<tr>
								<td height="5px" style="font-size: 5px; line-height: 5px;">&nbsp;</td>
							</tr>
							<tr>
								<td align="center">
									<table cellpadding="0" cellspacing="0" border="0" width="88%" style="width: 88%;">
										<tbody>
											<tr>
												<td>
													<div style="color: <?php echo esc_attr( $settings['body_text_color'] ); ?>; font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: <?php echo esc_attr( $settings['body_text_size'] . 'px' ); ?>; letter-spacing: -0.24px; line-height: <?php echo esc_attr( floor( $settings['body_text_size'] * 1.625 ) . 'px' ); ?>;">
														<?php echo wpautop( $formatted_tokens['reply.content'] ); ?>
													</div>
												</td>
											</tr>
										</tbody>
									</table>
								</td>
							</tr>
							<tr>
								<td height="5px" style="font-size: 5px; line-height: 5px;">&nbsp;</td>
							</tr>
						 </tbody>
					</table>
				</td>
			</tr>
		</table>
		<div class="spacer" style="font-size: 30px; line-height: 30px; height: 30px;">&nbsp;</div>
		<?php
		$output = str_replace( array( "\r", "\n" ), '', ob_get_clean() );

		return $output;
	}

	/**
	 * Generate the output for token discussion.content
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @param \BP_Email $bp_email
	 * @param array     $formatted_tokens
	 * @param array     $tokens
	 *
	 * @return string html for the output
	 */
	public function token__discussion_content( $bp_email, $formatted_tokens, $tokens ) {
		$output = '';

		if ( empty( $formatted_tokens['discussion.content'] ) || empty( $formatted_tokens['discussion.id'] ) ) {
			return $output;
		}

		$settings = bp_email_get_appearance_settings();

		ob_start();
		?>
		<div class="spacer" style="font-size: 5px; line-height: 5px; height: 5px;">&nbsp;</div>
		<table cellspacing="0" cellpadding="0" border="0" width="100%">
			<tr>
				<td align="center">
					<table cellpadding="0" cellspacing="0" border="0" width="100%">
						<tbody>
						<tr>
							<td valign="middle" width="65px" style="vertical-align: middle;">
								<a style="display: block; width: 47px;" href="<?php echo esc_attr( bp_core_get_user_domain( bbp_get_topic_author_id( $formatted_tokens['discussion.id'] ) ) ); ?>"
								   target="_blank" rel="nofollow">
									<?php
									$avatar_url = bp_core_fetch_avatar(
										array(
											'item_id' => bbp_get_topic_author_id( $formatted_tokens['discussion.id'] ),
											'width'   => 100,
											'height'  => 100,
											'type'    => 'full',
											'html'    => false,
										)
									);
									?>
									<img src="<?php echo esc_attr( $avatar_url ); ?>" width="47" height="47" border="0"
										 style="margin:0; padding:0; border:none; display:block; max-width: 47px; border-radius: 50%;" />
								</a>
							</td>
							<td width="88%" style="vertical-align: middle;">
								<div style="color: <?php echo esc_attr( $settings['body_secondary_text_color'] ); ?>; font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: <?php echo esc_attr( $settings['body_text_size'] . 'px' ); ?>; line-height: <?php echo esc_attr( $settings['body_text_size'] . 'px' ); ?>; letter-spacing: -0.24px;"><?php echo bbp_get_topic_author_display_name( $formatted_tokens['discussion.id'] ); ?></div>
							</td>
						</tr>
						</tbody>
					</table>
				</td>
			</tr>

			<tr>
				<td height="24px" style="font-size: 24px; line-height: 24px;">&nbsp;</td>
			</tr>

			<tr>
				<td>
					<table cellspacing="0" cellpadding="0" border="0" width="100%"
							style="background: <?php echo esc_attr( $settings['quote_bg'] ); ?>; border: 1px solid <?php echo esc_attr( $settings['body_border_color'] ); ?>; border-radius: 4px; border-collapse: separate !important">
						 <tbody>
							<tr>
								<td height="5px" style="font-size: 5px; line-height: 5px;">&nbsp;</td>
							</tr>
							<tr>
								<td align="center">
									<table cellpadding="0" cellspacing="0" border="0" width="88%" style="width: 88%;">
										<tbody>
											<tr>
												<td>
													<div style="color: <?php echo esc_attr( $settings['body_text_color'] ); ?>; font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: <?php echo esc_attr( $settings['body_text_size'] . 'px' ); ?>; letter-spacing: -0.24px; line-height: <?php echo esc_attr( floor( $settings['body_text_size'] * 1.625 ) . 'px' ); ?>;">
														<?php echo wpautop( $formatted_tokens['discussion.content'] ); ?>
													</div>
												</td>
											</tr>
										</tbody>
									</table>
								</td>
							</tr>
							<tr>
								<td height="5px" style="font-size: 5px; line-height: 5px;">&nbsp;</td>
							</tr>
						 </tbody>
					</table>
				</td>
			</tr>
		</table>
		<div class="spacer" style="font-size: 30px; line-height: 30px; height: 30px;">&nbsp;</div>
		<?php
		$output = str_replace( array( "\r", "\n" ), '', ob_get_clean() );

		return $output;
	}
}
