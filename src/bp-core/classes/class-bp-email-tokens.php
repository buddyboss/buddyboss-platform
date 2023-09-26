<?php
/**
 * Core component classes.
 *
 * @package BuddyBoss\Core
 *
 * @since BuddyBoss 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Represents an email tokens that will be sent in emails.
 *
 * @since BuddyBoss 1.0.0
 */
class BP_Email_Tokens {

	/**
	 * Message sender id.
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
		// set new email tokens added in BuddyBoss 1.0.0.
		add_filter( 'bp_email_set_tokens', array( $this, 'set_tokens' ), 10, 3 );

		// tokens for email after a new message is received, does not contain usable info about sender user
		// we need to acquire this info before we process tokens for that email
		// priority 9 is importent.
		add_action( 'messages_message_sent', array( $this, 'messages_message_sent' ), 9 );

		add_action( 'bp_email_get_property', array( $this, 'bb_email_subject_strip_all_tags' ), 9999, 3 );
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
		if ( 'html' === $bp_email->get_content_type() ) {
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
			'mentioned.content'    => array(
				'function'    => array( $this, 'token__mentioned_content' ),
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
			'sender.name'          => array(
				'function'    => array( $this, 'token__sender_name' ),
				'description' => __( 'Display the sender name with link.', 'buddyboss' ),
			),
			'group.name'           => array(
				'function'    => array( $this, 'token__group_name' ),
				'description' => __( 'Display the group name with link.', 'buddyboss' ),
			),
			'unread.count'         => array(
				'function'    => array( $this, 'token__unread_count' ),
				'description' => __( 'Display the unread count with link.', 'buddyboss' ),
			),
			'activity.content'     => array(
				'function'    => array( $this, 'token__activity_content' ),
				'description' => __( 'Display the activity post content, along with member\'s photo and name.', 'buddyboss' ),
			),
			'commenter.name'       => array(
				'function'    => array( $this, 'token__commenter_name' ),
				'description' => __( 'Display the commenter name.', 'buddyboss' ),
			),
			'commenter.url'        => array(
				'function'    => array( $this, 'token__commenter_url' ),
				'description' => __( 'Display the commenter link.', 'buddyboss' ),
			),
			'comment.url'          => array(
				'function'    => array( $this, 'token__comment_reply_url' ),
				'description' => __( 'Display the post comment url.', 'buddyboss' ),
			),
			'comment_reply'        => array(
				'function'    => array( $this, 'token__comment_reply' ),
				'description' => __( 'Display the post comment reply content.', 'buddyboss' ),
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
											<a class="group-avatar-wrap mobile-center" href="<?php echo bp_get_group_permalink( $group ); ?>" style="display: block; width: 104px;">
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
												<img alt="" src="<?php echo esc_url( $group_avatar ); ?>" width="100" height="100" border="0" style="margin: 2px; padding:0; box-sizing: border-box; border-radius: 3px; border: 3px solid <?php echo esc_attr( $settings['body_border_color'] ); ?>; display:block;" />
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
																	<?php
																	if ( 'hidden' === $group->status ) {
																		$invite_url = isset( $tokens['invites.url'] ) ? $tokens['invites.url'] : bp_get_group_permalink( $group );
																		?>
																		<table cellpadding="0" cellspacing="0" border="0" width="47%" style="width: 47%;" align="right" class="responsive-table">
																			<tbody>
																			<tr>
																				<td height="34px" align="right" style="vertical-align: middle;" class="mobile-padding-bottom">
																					<a class="mobile-button-center" href="<?php echo esc_url( $invite_url ); ?>" target="_blank" rel="nofollow" style="font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: <?php echo esc_attr( floor( $settings['body_text_size'] * 0.875 ) . 'px' ); ?>; text-decoration: none; display: inline-block; border-radius: 100px; text-align: center; min-height: 16px; line-height: 16px; background: <?php echo $settings['highlight_color']; ?>; color: #fff !important; min-width: 110px; padding: 8px;"><font style="color:#fff;"><?php _e( 'Visit Group', 'buddyboss' ); ?></font></a>
																				</td>
																			</tr>
																			</tbody>
																		</table>
																		<?php
																	} else {
																		?>
																		<table cellpadding="0" cellspacing="0" border="0" width="47%" style="width: 47%;" align="right" class="responsive-table">
																			<tbody>
																			<tr>
																				<td height="34px" align="right" style="vertical-align: middle;" class="mobile-padding-bottom">
																					<a class="mobile-button-center" href="<?php echo bp_get_group_permalink( $group ); ?>" target="_blank" rel="nofollow" style="font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: <?php echo esc_attr( floor( $settings['body_text_size'] * 0.875 ) . 'px' ); ?>; text-decoration: none; display: inline-block; border-radius: 100px; text-align: center; min-height: 16px; line-height: 16px; background: <?php echo $settings['highlight_color']; ?>; color: #fff !important; min-width: 110px; padding: 8px;"><font style="color:#fff;"><?php _e( 'Visit Group', 'buddyboss' ); ?></font></a>
																				</td>
																			</tr>
																			</tbody>
																		</table>
																		<?php
																	}
																	?>
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
					echo "<tr><td colspan='100%'><img src='{$cover_image}' alt='' /></td></tr>";
				}
				?>

				<tr>
					<td>
					<?php
						$avatar_url = bp_core_fetch_avatar(
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
						<a href="<?php echo esc_url( bp_get_group_permalink( $group ) ); ?>" style="border: 1px solid <?php echo esc_attr( $settings['body_border_color'] ); ?>; display: block; border-radius: 3px; width: 100px;">
							<img alt="" src="<?php echo esc_url( $avatar_url ); ?>" width="100" height="100" style="margin:0; padding:0; box-sizing: border-box; border-radius: 3px; border:3px solid <?php echo esc_attr( $settings['body_bg'] ); ?>; display:block;" border="0"/>
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
												$output .= sprintf( "<img src='%s' alt='%s' />", esc_url( $avatar ), esc_attr( bp_core_get_user_displayname( $user_id ) ) );
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
								<a style="display: block; width: 47px;" href="<?php echo esc_url( bp_core_get_user_domain( $activity->user_id ) ); ?>" target="_blank" rel="nofollow">
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
									<img alt="" src="<?php echo esc_url( $avatar_url ); ?>" width="47" height="47" style="margin:0; padding:0; border:none; display:block; max-width: 47px; border-radius: 50%;" border="0" />
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
					<a href="<?php echo esc_url( $tokens['mentioned.url'] ); ?>" target="_blank" rel="nofollow" style="font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: 14px; color: <?php echo $settings['highlight_color']; ?>; text-decoration: none; display: inline-block; border: 1px solid <?php echo $settings['highlight_color']; ?>; border-radius: 100px;  min-width: 64px; text-align: center; height: 16px; line-height: 16px; padding:8px;"><?php esc_html_e( 'Reply', 'buddyboss' ); ?></a>
				</td>
			</tr>
		</table>
		<div class="spacer" style="font-size: 20px; line-height: 20px; height: 20px;">&nbsp;</div>
		<?php
		$output = str_replace( array( "\r", "\n" ), '', ob_get_clean() );

		return $output;
	}

	/**
	 * Generate the output for token mentioned.content
	 *
	 * @since BuddyBoss 1.9.3
	 *
	 * @param \BP_Email $bp_email
	 * @param array     $formatted_tokens
	 * @param array     $tokens
	 *
	 * @return string html for the output
	 */
	public function token__mentioned_content( $bp_email, $formatted_tokens, $tokens ) {
		global $bp;
		$output = '';

		$settings = bp_email_get_appearance_settings();

		$activity   = $tokens['activity'] ?? false;
		$content    = $tokens['mentioned.content'] ?? '';
		$author_id  = $tokens['author_id'] ?? 0;
		$reply_text = $tokens['reply_text'] ?? __( 'Reply', 'buddyboss' );
		$title      = $tokens['title_text'] ?? '';

		if ( empty( $activity ) && empty( $content ) ) {
			return $output;
		}

		$user_id = isset( $activity->user_id ) ? $activity->user_id : $author_id;

		ob_start();

		if ( $title ) {
			?>
			<p><div style="color: <?php echo esc_attr( $settings['body_secondary_text_color'] ); ?>; font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: <?php echo esc_attr( $settings['body_text_size'] . 'px' ); ?>; line-height: <?php echo esc_attr( $settings['body_text_size'] . 'px' ); ?>; letter-spacing: -0.24px; font-weight: bold;"><?php echo esc_html( $title ); ?></div></p>
			<?php
		}
		?>
		<table cellspacing="0" cellpadding="0" border="0" width="100%">
			<tr>
				<td align="center">
					<table cellpadding="0" cellspacing="0" border="0" width="100%">
						<tbody>
						<tr>
							<td valign="middle" width="65px" style="vertical-align: middle;">
								<a style="display: block; width: 47px;" href="<?php echo esc_url( bp_core_get_user_domain( $user_id ) ); ?>" target="_blank" rel="nofollow">
									<?php
									$avatar_url = bp_core_fetch_avatar(
										array(
											'item_id' => $user_id,
											'width'   => 100,
											'height'  => 100,
											'type'    => 'full',
											'html'    => false,
										)
									);
									?>
									<img alt="" src="<?php echo esc_url( $avatar_url ); ?>" width="47" height="47" style="margin:0; padding:0; border:none; display:block; max-width: 47px; border-radius: 50%;" border="0" />
								</a>
							</td>
							<td width="88%" style="vertical-align: middle;">
								<div style="color: <?php echo esc_attr( $settings['body_secondary_text_color'] ); ?>; font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: <?php echo esc_attr( $settings['body_text_size'] . 'px' ); ?>; line-height: <?php echo esc_attr( $settings['body_text_size'] . 'px' ); ?>; letter-spacing: -0.24px;"><?php echo bp_core_get_user_displayname( $user_id ); ?></div>
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
												if ( ! empty( $activity ) ) {

													if ( in_array( $activity->content, array( '&nbsp;', '&#8203;' ), true ) ) {
														$activity->content = '';
													}
													// Check if link embed or link preview and append the content accordingly.
													$link_embed = bp_activity_get_meta( $activity->id, '_link_embed', true );
													if ( empty( preg_replace( '/(?:<p>\s*<\/p>\s*)+|<p>(\s|(?:<br>|<\/br>|<br\/?>))*<\/p>/', '', $activity->content ) ) && ! empty( $link_embed ) ) {
														$activity->content .= $link_embed;
													}

													$removed_autoembed_filter = false;
													if (
														function_exists( 'bp_use_embed_in_activity' ) &&
														bp_use_embed_in_activity() &&
														method_exists( $bp->embed, 'autoembed' ) &&
														method_exists( $bp->embed, 'run_shortcode' )
													) {
														$removed_autoembed_filter = true;
														remove_filter( 'bp_get_activity_content_body', array( $bp->embed, 'autoembed' ), 8, 2 );
														remove_filter( 'bp_get_activity_content_body', array( $bp->embed, 'run_shortcode' ), 7, 2 );
													}

													// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
													echo apply_filters_ref_array( 'bp_get_activity_content_body', array( $activity->content, &$activity ) );

													if ( $removed_autoembed_filter ) {
														add_filter( 'bp_get_activity_content_body', array( $bp->embed, 'autoembed' ), 8, 2 );
														add_filter( 'bp_get_activity_content_body', array( $bp->embed, 'run_shortcode' ), 7, 2 );
													}
												} else {
													// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
													echo $content;
												}
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
					<a href="<?php echo esc_url( $tokens['mentioned.url'] ); ?>" target="_blank" rel="nofollow" style="font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: 14px; color: <?php echo $settings['highlight_color']; ?>; text-decoration: none; display: inline-block; border: 1px solid <?php echo $settings['highlight_color']; ?>; border-radius: 100px;  min-width: 64px; text-align: center; height: 16px; line-height: 16px; padding: 8px 28px;"><?php echo esc_html( $reply_text ); ?></a>
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
								<a style="display: block; width: 47px;" href="<?php echo esc_url( bp_core_get_user_domain( $activity_comment->user_id ) ); ?>" target="_blank" rel="nofollow">
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
									<img alt="" src="<?php echo esc_url( $avatar_url ); ?>" width="47" height="47" border="0" style="margin:0; padding:0; border:none; display:block; max-width: 47px; border-radius: 50%;" />
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
					<table cellspacing="0" cellpadding="0" border="0" width="100%" style="background: <?php echo esc_attr( $settings['quote_bg'] ); ?>; border: 1px solid <?php echo esc_attr( $settings['body_border_color'] ); ?>; border-radius: 4px; border-collapse: separate !important">
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
				<td><a href="<?php echo esc_url( $tokens['thread.url'] ); ?>" target="_blank" rel="nofollow" style="font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: 14px; color: <?php echo esc_attr( $settings['highlight_color'] ); ?>; text-decoration: none; display: inline-block; border: 1px solid <?php echo esc_attr( $settings['highlight_color'] ); ?>; border-radius: 100px; min-width: 64px; text-align: center; height: 16px; line-height: 16px; padding: 8px;"><?php esc_html_e( 'Reply', 'buddyboss' ); ?></a></td>
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

		if ( in_array( $bp_email->get( 'type' ), array( 'messages-unread-digest', 'group-message-digest' ), true ) ) {
			return $this->token__delay_message( $bp_email, $formatted_tokens, $tokens );
		}

		$allow_type = array(
			'group-message-email',
			'messages-unread',
		);

		if ( ! in_array( $bp_email->get( 'type' ), $allow_type, true ) ) {
			return $output;
		}

		$settings = bp_email_get_appearance_settings();

		// Check the thread is group or not.
		$thread_id    = 0;
		$message_id   = $tokens['message_id'] ?? 0;
		$group_name   = '';
		$group_link   = '';
		$group_avatar = '';
		$group        = $tokens['group'] ?? false;

		if ( ! empty( $message_id ) ) {
			$thread_id = bb_get_thread_id_by_message_id( $message_id );
		}

		if ( empty( $group ) ) {
			$group_id      = $tokens['group.id'] ?? false;
			$message_type  = '';
			$message_users = '';
			if ( empty( $group_id ) ) {
				$group_id      = bp_messages_get_meta( $message_id, 'group_id', true ); // group id.
				$message_users = bp_messages_get_meta( $message_id, 'group_message_users', true ); // all - individual.
				$message_type  = bp_messages_get_meta( $message_id, 'group_message_type', true ); // open - private.
			}

			if ( ! empty( $group_id ) && 'open' === $message_type && 'all' === $message_users ) {
				$group = groups_get_group( $group_id );
			}
		}

		if ( ! empty( $group ) ) {
			$group_name   = bp_get_group_name( $group );
			$group_link   = bp_get_group_permalink( $group );
			$group_avatar = bp_core_fetch_avatar(
				array(
					'item_id'    => $group->id,
					'avatar_dir' => 'group-avatars',
					'type'       => 'full',
					'object'     => 'group',
					'width'      => 200,
					'height'     => 200,
					'html'       => false,
				)
			);
		}

		$sender_name   = '';
		$sender_link   = '';
		$sender_avatar = '';

		if ( empty( $this->_message_sender_id ) ) {
			$this->_message_sender_id = ! empty( $tokens['sender.id'] ) ? $tokens['sender.id'] : 0;
		}

		if ( $this->_message_sender_id ) {
			$sender_name   = $tokens['sender.name'] ?? bp_core_get_user_displayname( $this->_message_sender_id );
			$sender_link   = bp_core_get_user_domain( $this->_message_sender_id );
			$sender_avatar = bp_core_fetch_avatar(
				array(
					'item_id' => $this->_message_sender_id,
					'width'   => 100,
					'height'  => 100,
					'type'    => 'full',
					'html'    => false,
				)
			);
		}

		$media_ids       = '';
		$total_media_ids = 0;
		if ( bp_is_active( 'media' ) && bp_is_messages_media_support_enabled() && ! empty( $tokens['message_id'] ) ) {
			$media_ids = bp_messages_get_meta( $tokens['message_id'], 'bp_media_ids', true );
			if ( ! empty( $media_ids ) ) {
				$media_ids       = explode( ',', $media_ids );
				$total_media_ids = count( $media_ids );
				$media_ids       = implode( ',', array_slice( $media_ids, 0, 5 ) );
			}
		}

		$video_ids       = '';
		$total_video_ids = 0;
		if ( bp_is_active( 'media' ) && bp_is_messages_video_support_enabled() && ! empty( $tokens['message_id'] ) ) {
			$video_ids = bp_messages_get_meta( $tokens['message_id'], 'bp_video_ids', true );
			if ( ! empty( $video_ids ) ) {
				$video_ids       = explode( ',', $video_ids );
				$total_video_ids = count( $video_ids );
				$video_ids       = implode( ',', array_slice( $video_ids, 0, 5 ) );
			}
		}

		$document_ids       = '';
		$total_document_ids = 0;
		if ( bp_is_active( 'media' ) && bp_is_messages_document_support_enabled() && ! empty( $tokens['message_id'] ) ) {
			$document_ids = bp_messages_get_meta( $tokens['message_id'], 'bp_document_ids', true );
			if ( ! empty( $document_ids ) ) {
				$document_ids       = explode( ',', $document_ids );
				$total_document_ids = count( $document_ids );
				$document_ids       = implode( ',', array_slice( $document_ids, 0, 5 ) );
			}
		}

		$gif_data = array();
		if ( bp_is_active( 'media' ) && bp_is_messages_gif_support_enabled() && ! empty( $tokens['message_id'] ) ) {
			$gif_data = bp_messages_get_meta( $tokens['message_id'], '_gif_data', true );
		}

		ob_start();
		?>
		<table cellspacing="0" cellpadding="0" border="0" width="100%">
			<?php if ( ! empty( $sender_name ) || ! empty( $group_name ) ) : ?>
				<tr>
					<td>
						<table cellpadding="0" cellspacing="0" border="0" width="100%" style="width: 100%">
							<tbody>
								<tr>
									<?php if ( ! empty( $group_name ) ) { ?>
										<td valign="middle" width="65px" style="vertical-align: middle;">
											<a style="display: block; width: 47px;" href="<?php echo esc_url( $group_link ); ?>" target="_blank" rel="nofollow">
												<img alt="" src="<?php echo esc_url( $group_avatar ); ?>" width="47" height="47" border="0" style="margin:0; padding:0; border:none; display:block; max-width: 47px; border-radius: 50%;" />
											</a>
										</td>
										<td width="88%" style="vertical-align: middle;">
											<div style="font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: <?php echo esc_attr( $settings['body_text_size'] . 'px' ); ?>; line-height: <?php echo esc_attr( $settings['body_text_size'] . 'px' ); ?>; letter-spacing: -0.24px;">
												<a href="<?php echo esc_url( $group_link ); ?>" target="_blank" rel="nofollow" style="color: <?php echo esc_attr( $settings['body_secondary_text_color'] ); ?> !important;text-decoration: none;">
													<?php echo esc_html( $group_name ); ?>
												</a>
											</div>
										</td>
									<?php } else { ?>
										<td valign="middle" width="65px" style="vertical-align: middle;">
											<a style="display: block; width: 47px;" href="<?php echo esc_url( $sender_link ); ?>" target="_blank" rel="nofollow">
												<img alt="" src="<?php echo esc_url( $sender_avatar ); ?>" width="47" height="47" border="0" style="margin:0; padding:0; border:none; display:block; max-width: 47px; border-radius: 50%;" />
											</a>
										</td>
										<td width="88%" style="vertical-align: middle;">
											<div style="font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: <?php echo esc_attr( $settings['body_text_size'] . 'px' ); ?>; line-height: <?php echo esc_attr( $settings['body_text_size'] . 'px' ); ?>; letter-spacing: -0.24px;">
												<a href="<?php echo esc_url( $sender_link ); ?>" target="_blank" rel="nofollow" style="color: <?php echo esc_attr( $settings['body_secondary_text_color'] ); ?> !important;text-decoration: none;">
													<?php echo esc_html( $sender_name ); ?>
												</a>
											</div>
										</td>
									<?php } ?>
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
					<table cellspacing="0" cellpadding="0" border="0" width="100%" style="background: <?php echo esc_attr( $settings['quote_bg'] ); ?>; border: 1px solid <?php echo esc_attr( $settings['body_border_color'] ); ?>; border-radius: 4px; border-collapse: separate !important">
						<tbody>
							<tr>
								<td height="25px" style="font-size: 25px; line-height: 25px;">&nbsp;</td>
							</tr>
							<tr>
								<td align="center">
									<table cellpadding="0" cellspacing="0" border="0" width="86%" style="width: 86%;">
										<tbody>
											<tr>
												<?php if ( ! empty( $group_name ) ) { ?>
													<td valign="middle" width="55px" style="vertical-align: top;">
														<a style="display: block; width: 47px;" href="<?php echo esc_url( $sender_link ); ?>" target="_blank" rel="nofollow">
															<img alt="" src="<?php echo esc_url( $sender_avatar ); ?>" width="40" height="40" border="0" style="margin:0; padding:0; border:none; display:block; width: 40px; height: 40px; border-radius: 50%;" />
														</a>
													</td>
												<?php } ?>

												<td width="<?php echo ! empty( $group_name ) ? esc_attr( '88%' ) : esc_attr( '100%' ); ?>" style="<?php echo ! empty( $group_name ) ? esc_attr( 'vertical-align: top;padding-bottom:20px;' ) : ''; ?>">
													<?php if ( ! empty( $group_name ) ) { ?>
														<a href="<?php echo esc_url( $sender_link ); ?>" target="_blank" rel="nofollow" style="color: <?php echo esc_attr( $settings['body_secondary_text_color'] ); ?>!important; font-weight: 500; text-decoration:none;"><?php echo esc_html( $sender_name ); ?></a>
													<?php } ?>
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

																$attachment_id = base64_encode( 'forbidden_' . bp_get_media_attachment_id() );
																$media_url     = home_url( '/' ) . 'bb-attachment-media-preview/' . $attachment_id . '/bb-media-activity-image/' . base64_encode( 'thread_' . $thread_id );
																?>
																<div class="bb-activity-media-elem"  style="width: 250px; vertical-align: top; height: 200px; overflow: hidden;">
																	<a href="<?php echo esc_url( $tokens['message.url'] ); ?>">
																		<img style="border-radius: 4px; width:100%; height: 100%; object-fit: cover;" src="<?php echo esc_url( $media_url ); ?>" alt="<?php echo esc_attr( bp_get_media_title() ); ?>" />
																	</a>
																</div>
																<?php if ( $total_media_ids > 1 ) : ?>
																	<p style="height: 6px;background: rgba(0, 0, 0, 0.3);border-radius: 0px 0px 4px 4px;max-width: 240px;margin: 0;margin-left: 5px;"></p>
																	<p style="height: 6px;background: rgba(0, 0, 0, 0.1);border-radius: 0px 0px 4px 4px;max-width: 222px;margin: 0;margin-left: 14px;"></p>
																<?php endif; ?>
																<?php
																break;
															}
															?>
														</div>
														<?php
													endif;

													if ( ! empty( $video_ids ) && bp_has_video(
														array(
															'include' => $video_ids,
															'order_by' => 'menu_order',
															'sort' => 'ASC',
														)
													) ) :
														?>
														<div class="bb-activity-media-wrap" style="padding: 10px 0;">
															<?php
															while ( bp_video() ) {
																bp_the_video();

																$poster_thumb = bp_get_video_activity_thumb();
																if ( empty( $poster_thumb ) ) {
																	$poster_thumb = bp_get_video_popup_thumb();
																}
																if ( empty( $poster_thumb ) ) {
																	$poster_thumb = bb_get_video_default_placeholder_image();
																}
																?>
																<div class="bb-activity-media-elem"  style="background-image: url('<?php echo esc_url( $poster_thumb ); ?>'); background-size:cover; display: block; width: 250px; vertical-align: top; height: 145px; overflow: hidden; padding: 0; border-radius: 4px;">
																	<a href="<?php echo esc_url( $tokens['message.url'] ); ?>">
																		<img style="display: block; height: 60px;width: 60px; background-color: #fff; border-radius: 50%; margin: 42.5px 0 0 95px" src="<?php echo esc_url( buddypress()->plugin_url ); ?>bp-templates/bp-nouveau/images/video-play.svg" alt="<?php echo esc_attr( bp_get_video_title() ); ?>" />
																	</a>
																</div>
																<?php if ( $total_video_ids > 1 ) : ?>
																	<p style="height: 6px;background: rgba(0, 0, 0, 0.3);border-radius: 0px 0px 4px 4px;max-width: 240px;margin: 0;margin-left: 5px;"></p>
																	<p style="height: 6px;background: rgba(0, 0, 0, 0.1);border-radius: 0px 0px 4px 4px;max-width: 222px;margin: 0;margin-left: 14px;"></p>
																<?php endif; ?>
																<?php
																break;
															}
															?>
														</div>
														<?php
													endif;

													if (
														! empty( $document_ids ) &&
														bp_has_document(
															array(
																'include'  => $document_ids,
																'order_by' => 'menu_order',
																'sort'     => 'ASC',
																'per_page' => 5,
															)
														)
													) :
														?>
														<div class="bb-activity-media-wrap" style="padding: 10px 0;">
															<?php
															while ( bp_document() ) {
																bp_the_document();

																$attachment_id = bp_get_document_attachment_id();
																$filename      = basename( get_attached_file( $attachment_id ) );
																$size          = is_file( get_attached_file( $attachment_id ) ) ? bp_document_size_format( filesize( get_attached_file( $attachment_id ) ) ) : 0;
																$extension     = bp_get_document_extension();
																?>
																<div class="bb-activity-media-elem">
																	<a href="<?php echo esc_url( $tokens['message.url'] ); ?>" style="font-size:14px; text-decoration:none;">
																		<span style="font-weight:500;"><?php echo esc_html( $filename ); ?></span>
																		<span style="font-size: 13px; margin-left:5px; color: <?php echo esc_attr( $settings['body_text_color'] ); ?>;"><?php echo esc_html( $size ); ?></span>
																		<span style="font-size: 13px; margin-left:3px; text-transform: uppercase; color: <?php echo esc_attr( $settings['body_text_color'] ); ?>;"><?php echo $extension ? esc_attr( $extension ) : ''; ?></span>
																	</a>
																</div>
																<?php
															}
															?>
															<?php if ( $total_document_ids > 5 ) : ?>
																<a href="<?php echo esc_url( $tokens['message.url'] ); ?>"><?php echo sprintf( __( 'and %d more', 'buddyboss' ), $total_document_ids - 5 ); ?></a>
															<?php endif; ?>
														</div>
														<?php
													endif;

													if ( ! empty( $gif_data ) ) :
														?>
														<div class="activity-attached-gif-container">
															<div class="gif-image-container">
																<a href="<?php echo esc_url( $tokens['message.url'] ); ?>" class="gif-play-button">
																	<?php if ( is_int( $gif_data['still'] ) ) { ?>
																		<img style="max-width: 250px;max-height: 185px;object-fit: cover;border-radius: 4px;" alt="" src="<?php echo esc_url( wp_get_attachment_url( $gif_data['still'] ) ); ?>" />
																	<?php } else { ?>
																		<img style="max-width: 250px;max-height: 185px;object-fit: cover;border-radius: 4px;" alt="" src="<?php echo esc_url( $gif_data['still'] ); ?>" />
																	<?php } ?>
																</a>
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
					<a href="<?php echo esc_url( $tokens['message.url'] ); ?>" target="_blank" rel="nofollow" style="font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: 14px; color: <?php echo esc_attr( $settings['highlight_color'] ); ?>; text-decoration: none; display: inline-block; border: 1px solid <?php echo esc_attr( $settings['highlight_color'] ); ?>; border-radius: 100px; min-width: 64px; text-align: center; height: 16px; line-height: 16px; padding: 8px;"><?php esc_html_e( 'Reply', 'buddyboss' ); ?></a>
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
			case 'new-follower':
				$member_id = isset( $tokens['follower.id'] ) ? $tokens['follower.id'] : false;
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
											<?php
												$avatar_src = bp_core_fetch_avatar(
													array(
														'item_id' => $member_id,
														'width' => 280,
														'height' => 280,
														'type' => 'full',
														'html' => false,
													)
												);
											?>
											<a class="avatar-wrap mobile-center" href="<?php echo esc_url( bp_core_get_user_domain( $member_id ) ); ?>" style="display: block; border-radius: 3px; width: 140px;">
												<img alt="" src="<?php echo esc_url( $avatar_src ); ?>" width="140" height="140" style="margin:0; padding:0; border:none;float:left;" border="0" />
											</a>
										</td>
										<td width="4%" class="mobile-hide">&nbsp;</td>
										<td width="72%" class="mobile-block-padding-full">
											<table cellpadding="0" cellspacing="0" border="0" width="100%" style="width: 100%;">
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

		remove_filter( 'bp_get_group_description_excerpt', 'bb_get_group_description_excerpt_view_more', 99, 2 );

		$group_excerpt = bp_get_group_description_excerpt( $group );

		add_filter( 'bp_get_group_description_excerpt', 'bb_get_group_description_excerpt_view_more', 99, 2 );

		if ( empty( $group ) || empty( $group_excerpt ) ) {
			return $output;
		}

		$settings = bp_email_get_appearance_settings();

		ob_start();
		?>
		<div class="spacer" style="font-size: 5px; line-height: 5px; height: 5px;">&nbsp;</div>
		<table cellspacing="0" cellpadding="0" border="0" width="100%" style="background: <?php echo esc_attr( $settings['quote_bg'] ); ?>; border: 1px solid <?php echo esc_attr( $settings['body_border_color'] ); ?>; border-radius: 4px; border-collapse: separate !important">
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
		<table cellspacing="0" cellpadding="0" border="0" width="100%" style="background: <?php echo esc_attr( $settings['quote_bg'] ); ?>; border: 1px solid <?php echo esc_attr( $settings['body_border_color'] ); ?>; border-radius: 4px; border-collapse: separate !important">
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

		if ( empty( $formatted_tokens['reply.id'] ) ) {
			return $output;
		}

		$settings = bp_email_get_appearance_settings();

		$media_ids       = '';
		$total_media_ids = 0;
		if ( bp_is_active( 'media' ) && bp_is_forums_media_support_enabled() && ! empty( $tokens['reply.id'] ) ) {
			$media_ids = get_post_meta( $tokens['reply.id'], 'bp_media_ids', true );
			if ( ! empty( $media_ids ) ) {
				$media_ids       = explode( ',', $media_ids );
				$total_media_ids = count( $media_ids );
				$media_ids       = implode( ',', array_slice( $media_ids, 0, 5 ) );
			}
		}

		$video_ids       = '';
		$total_video_ids = 0;
		if ( bp_is_active( 'media' ) && bp_is_forums_video_support_enabled() && ! empty( $tokens['reply.id'] ) ) {
			$video_ids = get_post_meta( $tokens['reply.id'], 'bp_video_ids', true );
			if ( ! empty( $video_ids ) ) {
				$video_ids       = explode( ',', $video_ids );
				$total_video_ids = count( $video_ids );
				$video_ids       = implode( ',', array_slice( $video_ids, 0, 5 ) );
			}
		}

		$document_ids       = '';
		$total_document_ids = 0;
		if ( bp_is_active( 'media' ) && bp_is_forums_document_support_enabled() && ! empty( $tokens['reply.id'] ) ) {
			$document_ids = get_post_meta( $tokens['reply.id'], 'bp_document_ids', true );
			if ( ! empty( $document_ids ) ) {
				$document_ids       = explode( ',', $document_ids );
				$total_document_ids = count( $document_ids );
				$document_ids       = implode( ',', array_slice( $document_ids, 0, 5 ) );
			}
		}

		$gif_data = array();
		if ( bp_is_active( 'media' ) && bp_is_forums_gif_support_enabled() && ! empty( $tokens['reply.id'] ) ) {
			$gif_data = get_post_meta( $tokens['reply.id'], '_gif_data', true );
		}

		if (
			empty( $formatted_tokens['reply.content'] ) &&
			empty( $gif_data ) &&
			empty( $document_ids ) &&
			empty( $video_ids ) &&
			empty( $media_ids )
		) {
			return $output;
		}

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
								<a style="display: block; width: 47px;" href="<?php echo esc_url( bp_core_get_user_domain( bbp_get_reply_author_id( $formatted_tokens['reply.id'] ) ) ); ?>" target="_blank" rel="nofollow">
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
									<img alt="" src="<?php echo esc_url( $avatar_url ); ?>" width="47" height="47" border="0" style="margin:0; padding:0; border:none; display:block; max-width: 47px; border-radius: 50%;" />
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
					<table cellspacing="0" cellpadding="0" border="0" width="100%" style="background: <?php echo esc_attr( $settings['quote_bg'] ); ?>; border: 1px solid <?php echo esc_attr( $settings['body_border_color'] ); ?>; border-radius: 4px; border-collapse: separate !important">
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
											<?php
											if (
												! empty( $media_ids ) &&
												bp_has_media(
													array(
														'include'  => $media_ids,
														'order_by' => 'menu_order',
														'sort'     => 'ASC',
														'privacy'  => false,
													)
												)
                                            ) :
												?>
												<tr>
													<td>
														<div class="bb-activity-media-wrap" style="padding: 5px 0 10px;">
														<?php
														while ( bp_media() ) {
															bp_the_media();

															$media_id      = 'forbidden_' . bp_get_media_id();
															$attachment_id = 'forbidden_' . bp_get_media_attachment_id();
															$media_url     = home_url( '/' ) . 'bb-media-preview/' . base64_encode( $attachment_id ) . '/' . base64_encode( $media_id ) . '/bb-media-activity-image';
															?>
															<div class="bb-activity-media-elem"  style="width: 250px; vertical-align: top; overflow: hidden;">
																<a href="<?php echo esc_url( $tokens['reply.url'] ); ?>">
																	<img style="border-radius: 4px; width:100%; height: 100%; object-fit: cover;" src="<?php echo esc_url( $media_url ); ?>" alt="<?php echo esc_attr( bp_get_media_title() ); ?>" />
																</a>
															</div>
															<?php if ( $total_media_ids > 1 ) : ?>
																<p style="height: 6px;background: rgba(0, 0, 0, 0.3);border-radius: 0px 0px 4px 4px;max-width: 240px;margin: 0;margin-left: 5px;"></p>
																<p style="height: 6px;background: rgba(0, 0, 0, 0.1);border-radius: 0px 0px 4px 4px;max-width: 222px;margin: 0;margin-left: 14px;"></p>
															<?php endif; ?>
															<?php
															break;
														}
														?>
													</div>
													</td>
												</tr>
												<?php
											endif;

											if (
												! empty( $video_ids ) &&
												bp_has_video(
													array(
														'include'  => $video_ids,
														'order_by' => 'menu_order',
														'sort'     => 'ASC',
														'privacy'  => false,
													)
												)
											) :
												?>
												<tr>
													<td>
														<div class="bb-activity-media-wrap" style="padding: 5px 0 10px;">
															<?php
															while ( bp_video() ) {
																bp_the_video();

																$poster_thumb = bp_get_video_activity_thumb();
																if ( empty( $poster_thumb ) ) {
																	$poster_thumb = bp_get_video_popup_thumb();
																}
																if ( empty( $poster_thumb ) ) {
																	$poster_thumb = bb_get_video_default_placeholder_image();
																}
																?>
																<div class="bb-activity-media-elem"  style="background-image: url('<?php echo esc_url( $poster_thumb ); ?>'); background-size:cover; display: block; width: 250px; vertical-align: top; height: 145px; overflow: hidden; padding: 0; border-radius: 4px;">
																	<a href="<?php echo esc_url( $tokens['reply.url'] ); ?>">
																		<img style="display: block; height: 60px;width: 60px; background-color: #fff; border-radius: 50%; margin: 42.5px 0 0 95px" src="<?php echo esc_url( buddypress()->plugin_url ); ?>bp-templates/bp-nouveau/images/video-play.svg" alt="<?php echo esc_attr( bp_get_video_title() ); ?>" />
																	</a>
																</div>
																<?php if ( $total_video_ids > 1 ) : ?>
																	<p style="height: 6px;background: rgba(0, 0, 0, 0.3);border-radius: 0px 0px 4px 4px;max-width: 240px;margin: 0;margin-left: 5px;"></p>
																	<p style="height: 6px;background: rgba(0, 0, 0, 0.1);border-radius: 0px 0px 4px 4px;max-width: 222px;margin: 0;margin-left: 14px;"></p>
																<?php endif; ?>
																<?php
																break;
															}
															?>
														</div>
													</td>
												</tr>
												<?php
											endif;

											if (
												! empty( $document_ids ) &&
												bp_has_document(
													array(
														'include'  => $document_ids,
														'order_by' => 'menu_order',
														'sort'     => 'ASC',
														'privacy'  => false,
														'per_page' => 5,
													)
												)
											) :
												?>
												<tr>
													<td>
														<div style="padding: 5px 0 10px;">
														<?php
														while ( bp_document() ) {
															bp_the_document();

															$attachment_id = bp_get_document_attachment_id();
															$filename      = basename( get_attached_file( $attachment_id ) );
															$size          = is_file( get_attached_file( $attachment_id ) ) ? bp_document_size_format( filesize( get_attached_file( $attachment_id ) ) ) : 0;
															$extension     = bp_get_document_extension();
															?>
															<div class="bb-activity-media-elem">
																<a href="<?php echo esc_url( $tokens['reply.url'] ); ?>" style="font-size:14px; text-decoration:none;">
																	<span style="font-weight:500;"><?php echo esc_html( $filename ); ?></span>
																	<span style="font-size: 13px; margin-left:5px; color: <?php echo esc_attr( $settings['body_text_color'] ); ?>;"><?php echo esc_html( $size ); ?></span>
																	<span style="font-size: 13px; margin-left:3px; text-transform: uppercase; color: <?php echo esc_attr( $settings['body_text_color'] ); ?>;"><?php echo $extension ? esc_attr( $extension ) : ''; ?></span>
																</a>
															</div>
															<?php
														}

														if ( $total_document_ids > 5 ) :
															?>
															<a href="<?php echo esc_url( $tokens['reply.url'] ); ?>">
																<?php
																echo sprintf(
																	/* translators: The more documents. */
																	esc_html__( 'and %d more', 'buddyboss' ),
																	(int) ( $total_document_ids - 5 )
																);
																?>
															</a>
														<?php endif; ?>
													</div>
													</td>
												</tr>
												<?php
											endif;

											if ( ! empty( $gif_data ) ) :
												?>
												<tr>
													<td>
														<div class="activity-attached-gif-container">
															<div class="gif-image-container">
																<a href="<?php echo esc_url( $tokens['reply.url'] ); ?>">
																	<?php if ( is_int( $gif_data['still'] ) ) { ?>
																		<img style="max-width: 250px;max-height: 185px;object-fit: cover;border-radius: 4px;" alt="" src="<?php echo esc_url( wp_get_attachment_url( $gif_data['still'] ) ); ?>" />
																	<?php } else { ?>
																		<img style="max-width: 250px;max-height: 185px;object-fit: cover;border-radius: 4px;" alt="" src="<?php echo esc_url( $gif_data['still'] ); ?>" />
																	<?php } ?>
																</a>
															</div>
														</div>
													</td>
												</tr>
												<?php
											endif;
										?>
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

		if ( empty( $formatted_tokens['discussion.id'] ) ) {
			return $output;
		}

		if ( 'groups-new-discussion' === $bp_email->get( 'type' ) ) {
			return $this->token__group_discussion_content( $bp_email, $formatted_tokens, $tokens );
		}

		$settings = bp_email_get_appearance_settings();

		$media_ids       = '';
		$total_media_ids = 0;
		if ( bp_is_active( 'media' ) && bp_is_forums_media_support_enabled() && ! empty( $tokens['discussion.id'] ) ) {
			$media_ids = get_post_meta( $tokens['discussion.id'], 'bp_media_ids', true );
			if ( ! empty( $media_ids ) ) {
				$media_ids       = explode( ',', $media_ids );
				$total_media_ids = count( $media_ids );
				$media_ids       = implode( ',', array_slice( $media_ids, 0, 5 ) );
			}
		}

		$video_ids       = '';
		$total_video_ids = 0;
		if ( bp_is_active( 'media' ) && bp_is_forums_video_support_enabled() && ! empty( $tokens['discussion.id'] ) ) {
			$video_ids = get_post_meta( $tokens['discussion.id'], 'bp_video_ids', true );
			if ( ! empty( $video_ids ) ) {
				$video_ids       = explode( ',', $video_ids );
				$total_video_ids = count( $video_ids );
				$video_ids       = implode( ',', array_slice( $video_ids, 0, 5 ) );
			}
		}

		$document_ids       = '';
		$total_document_ids = 0;
		if ( bp_is_active( 'media' ) && bp_is_forums_document_support_enabled() && ! empty( $tokens['discussion.id'] ) ) {
			$document_ids = get_post_meta( $tokens['discussion.id'], 'bp_document_ids', true );
			if ( ! empty( $document_ids ) ) {
				$document_ids       = explode( ',', $document_ids );
				$total_document_ids = count( $document_ids );
				$document_ids       = implode( ',', array_slice( $document_ids, 0, 5 ) );
			}
		}

		$gif_data = array();
		if ( bp_is_active( 'media' ) && bp_is_forums_gif_support_enabled() && ! empty( $tokens['discussion.id'] ) ) {
			$gif_data = get_post_meta( $tokens['discussion.id'], '_gif_data', true );
		}

		if (
			empty( $formatted_tokens['discussion.content'] ) &&
			empty( $gif_data ) &&
			empty( $document_ids ) &&
			empty( $video_ids ) &&
			empty( $media_ids )
		) {
			return $output;
		}

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
								<a style="display: block; width: 47px;" href="<?php echo esc_url( bp_core_get_user_domain( bbp_get_topic_author_id( $formatted_tokens['discussion.id'] ) ) ); ?>" target="_blank" rel="nofollow">
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
									<img src="<?php echo esc_url( $avatar_url ); ?>" width="47" height="47" border="0" style="margin:0; padding:0; border:none; display:block; max-width: 47px; border-radius: 50%;" />
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
					<table cellspacing="0" cellpadding="0" border="0" width="100%" style="background: <?php echo esc_attr( $settings['quote_bg'] ); ?>; border: 1px solid <?php echo esc_attr( $settings['body_border_color'] ); ?>; border-radius: 4px; border-collapse: separate !important">
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
											<?php
											if ( ! empty( $media_ids ) && bp_has_media(
												array(
													'include'  => $media_ids,
													'order_by' => 'menu_order',
													'sort'     => 'ASC',
													'privacy'  => false,
												)
											) ) :
												?>
												<tr>
													<td>
														<div class="bb-activity-media-wrap" style="padding: 5px 0 10px;">
														<?php
														while ( bp_media() ) {
															bp_the_media();

															$media_id      = 'forbidden_' . bp_get_media_id();
															$attachment_id = 'forbidden_' . bp_get_media_attachment_id();
															$media_url     = home_url( '/' ) . 'bb-media-preview/' . base64_encode( $attachment_id ) . '/' . base64_encode( $media_id ) . '/bb-media-activity-image';
															?>
															<div class="bb-activity-media-elem"  style="width: 250px; vertical-align: top; overflow: hidden;">
																<a href="<?php echo esc_url( $tokens['discussion.url'] ); ?>">
																	<img style="border-radius: 4px; width:100%; height: 100%; object-fit: cover;" src="<?php echo esc_url( $media_url ); ?>" alt="<?php echo esc_attr( bp_get_media_title() ); ?>" />
																</a>
															</div>
															<?php if ( $total_media_ids > 1 ) : ?>
																<p style="height: 6px;background: rgba(0, 0, 0, 0.3);border-radius: 0px 0px 4px 4px;max-width: 240px;margin: 0;margin-left: 5px;"></p>
																<p style="height: 6px;background: rgba(0, 0, 0, 0.1);border-radius: 0px 0px 4px 4px;max-width: 222px;margin: 0;margin-left: 14px;"></p>
															<?php endif; ?>
															<?php
															break;
														}
														?>
													</div>
													</td>
												</tr>
												<?php
											endif;

											if ( ! empty( $video_ids ) && bp_has_video(
												array(
													'include'  => $video_ids,
													'order_by' => 'menu_order',
													'sort'     => 'ASC',
													'privacy'  => false,
												)
											) ) :
												?>
												<tr>
													<td>
														<div class="bb-activity-media-wrap" style="padding: 5px 0 10px;">
															<?php
															while ( bp_video() ) {
																bp_the_video();

																$poster_thumb = bp_get_video_activity_thumb();
																if ( empty( $poster_thumb ) ) {
																	$poster_thumb = bp_get_video_popup_thumb();
																}
																if ( empty( $poster_thumb ) ) {
																	$poster_thumb = bb_get_video_default_placeholder_image();
																}
																?>
																<div class="bb-activity-media-elem"  style="background-image: url('<?php echo esc_url( $poster_thumb ); ?>'); background-size:cover; display: block; width: 250px; vertical-align: top; height: 145px; overflow: hidden; padding: 0; border-radius: 4px;">
																	<a href="<?php echo esc_url( $tokens['discussion.url'] ); ?>">
																		<img style="display: block; height: 60px;width: 60px; background-color: #fff; border-radius: 50%; margin: 42.5px 0 0 95px" src="<?php echo esc_url( buddypress()->plugin_url ); ?>bp-templates/bp-nouveau/images/video-play.svg" alt="<?php echo esc_attr( bp_get_video_title() ); ?>" />
																	</a>
																</div>
																<?php if ( $total_video_ids > 1 ) : ?>
																	<p style="height: 6px;background: rgba(0, 0, 0, 0.3);border-radius: 0px 0px 4px 4px;max-width: 240px;margin: 0;margin-left: 5px;"></p>
																	<p style="height: 6px;background: rgba(0, 0, 0, 0.1);border-radius: 0px 0px 4px 4px;max-width: 222px;margin: 0;margin-left: 14px;"></p>
																<?php endif; ?>
																<?php
																break;
															}
															?>
														</div>
													</td>
												</tr>
												<?php
											endif;

											if (
												! empty( $document_ids ) &&
												bp_has_document(
													array(
														'include'  => $document_ids,
														'order_by' => 'menu_order',
														'sort'     => 'ASC',
														'privacy'  => false,
														'per_page' => 5,
													)
												)
											) :
												?>
												<tr>
													<td>
														<div style="padding: 5px 0 10px;">
														<?php
														while ( bp_document() ) {
															bp_the_document();

															$attachment_id = bp_get_document_attachment_id();
															$filename      = basename( get_attached_file( $attachment_id ) );
															$size          = is_file( get_attached_file( $attachment_id ) ) ? bp_document_size_format( filesize( get_attached_file( $attachment_id ) ) ) : 0;
															$extension     = bp_get_document_extension();
															?>
															<div class="bb-activity-media-elem">
																<a href="<?php echo esc_url( $tokens['discussion.url'] ); ?>" style="font-size:14px; text-decoration:none;">
																	<span style="font-weight:500;"><?php echo esc_html( $filename ); ?></span>
																	<span style="font-size: 13px; margin-left:5px; color: <?php echo esc_attr( $settings['body_text_color'] ); ?>;"><?php echo esc_html( $size ); ?></span>
																	<span style="font-size: 13px; margin-left:3px; text-transform: uppercase; color: <?php echo esc_attr( $settings['body_text_color'] ); ?>;"><?php echo $extension ? esc_attr( $extension ) : ''; ?></span>
																</a>
															</div>
															<?php
														}

														if ( $total_document_ids > 5 ) :
															?>
															<a href="<?php echo esc_url( $tokens['discussion.url'] ); ?>">
																<?php
																echo sprintf(
																/* translators: The more documents. */
																	__( 'and %d more', 'buddyboss' ),
																	$total_document_ids - 5
																);
																?>
															</a>
														<?php endif; ?>
													</div>
													</td>
												</tr>
												<?php
											endif;

											if ( ! empty( $gif_data ) ) :
												?>
												<tr>
													<td>
														<div class="activity-attached-gif-container">
															<div class="gif-image-container">
																<a href="<?php echo esc_url( $tokens['discussion.url'] ); ?>">
																	<?php if ( is_int( $gif_data['still'] ) ) { ?>
																		<img style="max-width: 250px;max-height: 185px;object-fit: cover;border-radius: 4px;" alt="" src="<?php echo esc_url( wp_get_attachment_url( $gif_data['still'] ) ); ?>" />
																	<?php } else { ?>
																		<img style="max-width: 250px;max-height: 185px;object-fit: cover;border-radius: 4px;" alt="" src="<?php echo esc_url( $gif_data['still'] ); ?>" />
																	<?php } ?>
																</a>
															</div>
														</div>
													</td>
												</tr>
												<?php
											endif;
										?>
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
	 * Generate the output for token delay message
	 *
	 * @since BuddyBoss 2.1.4
	 *
	 * @param \BP_Email $bp_email         Core component classes.
	 * @param array     $formatted_tokens Formatted token array.
	 * @param array     $tokens           Token array.
	 *
	 * @return string html for the output
	 */
	public function token__delay_message( $bp_email, $formatted_tokens, $tokens ) {
		$output = '';

		$allow_type = array(
			'group-message-digest',
			'messages-unread-digest',
		);

		if ( ! in_array( $bp_email->get( 'type' ), $allow_type, true ) || empty( $tokens['message'] ) ) {
			return $output;
		}

		$settings   = bp_email_get_appearance_settings();
		$sender_ids = array_column( $tokens['message'], 'sender_id' );
		$sender_ids = array_unique( wp_parse_id_list( $sender_ids ) );

		// Find the group.
		$group = $tokens['group'] ?? false;
		if ( empty( $group ) ) {
			$group_id    = $tokens['group.id'] ?? false;
			$message_ids = array_column( $tokens['message'], 'message_id' );
			$message_ids = array_unique( wp_parse_id_list( $message_ids ) );
			$message_id  = ! empty( $message_ids ) ? current( $message_ids ) : false;
			if ( empty( $group_id ) ) {
				$group_id    = bp_messages_get_meta( $message_id, 'group_id', true ); // group id.
			}

			if ( ! empty( $group_id ) ) {
				$message_users = bp_messages_get_meta( $message_id, 'group_message_users', true ); // all - individual.
				$message_type  = bp_messages_get_meta( $message_id, 'group_message_type', true ); // open - private.
				if ( 'open' === $message_type && 'all' === $message_users ) {
					$group = groups_get_group( $group_id );
				}
			}
		}

		ob_start();
		?>
		<table cellspacing="0" cellpadding="0" border="0" width="100%">
			<?php if ( ! empty( $sender_ids ) || ! empty( $group ) ) : ?>
				<tr>
					<td>
						<table cellpadding="0" cellspacing="0" border="0" width="100%" style="width: 100%">
							<tbody>
							<?php
							if ( ! empty( $group ) ) {
								$group_avatar = bp_core_fetch_avatar(
									array(
										'item_id'    => $group->id,
										'avatar_dir' => 'group-avatars',
										'type'       => 'full',
										'object'     => 'group',
										'width'      => 200,
										'height'     => 200,
										'html'       => false,
									)
								);
								?>
								<tr>
									<td valign="middle" width="65px" style="vertical-align: middle;">
										<a style="display: block; width: 52px;" href="<?php echo esc_url( bp_get_group_permalink( $group ) ); ?>" target="_blank" rel="nofollow">
											<img alt="" src="<?php echo esc_url( $group_avatar ); ?>" width="52" height="52" border="0" style="margin:0; padding:0; border:none; display:block; width: 52px; height: 52px; border-radius: 50%;" />
										</a>
									</td>
									<td width="88%" style="vertical-align: middle;">
										<div style="font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: <?php echo esc_attr( $settings['body_text_size'] . 'px' ); ?>; line-height: <?php echo esc_attr( $settings['body_text_size'] . 'px' ); ?>; letter-spacing: -0.24px; font-weight: 500;">
											<a href="<?php echo esc_url( bp_get_group_permalink( $group ) ); ?>" target="_blank" rel="nofollow" style="color: <?php echo esc_attr( $settings['body_secondary_text_color'] ); ?> !important;text-decoration: none;"><?php echo esc_html( bp_get_group_name( $group ) ); ?></a>
										</div>
									</td>
								</tr>
								<?php
							} elseif ( ! empty( $sender_ids ) ) {

								$sender_avatars    = array();
								$sender_names      = array();
								$avatars_count     = count( $sender_ids );
								$avatars_iteration = 0;
								foreach ( $sender_ids as $sender_id ) {

									$avatar_url = bp_core_fetch_avatar(
										array(
											'item_id' => $sender_id,
											'width'   => 100,
											'height'  => 100,
											'type'    => 'full',
											'html'    => false,
										)
									);

									if ( $avatars_count > 1 ) {

										if ( 0 === $avatars_iteration ) {
											$sender_avatars[] = '<div style="height:17px;with:17px;"><a style="display: block; width: 35px;" href="' . esc_url( bp_core_get_user_domain( $sender_id ) ) . '" target="_blank" rel="nofollow"><img alt="" src="' . esc_url( $avatar_url ) . '" width="47" height="47" border="0" style="margin:0; padding:0; border:none; display:block; width: 35px; height: 35px; border-radius: 50%;" /></a></div>';
										} elseif ( 1 === $avatars_iteration ) {
											$sender_avatars[] = '<div style="padding-left: 17px; opacity: 0.999;"><a style="display: block; width: 35px;" href="' . esc_url( bp_core_get_user_domain( $sender_id ) ) . '" target="_blank" rel="nofollow"><img alt="" src="' . esc_url( $avatar_url ) . '" width="47" height="47" border="0" style="margin:0; padding:0; border:none; display:block; width: 35px; height: 35px; border-radius: 50%; border: 2px solid #fff;" /></a></div>';
										}
									} else {
										$sender_avatars[] = '<div><a style="display: block;" href="' . esc_url( bp_core_get_user_domain( $sender_id ) ) . '" target="_blank" rel="nofollow"><img alt="" src="' . esc_url( $avatar_url ) . '" width="52" height="52" border="0" style="margin:0; padding:0; border:none; display:block; width: 52px; height: 52px; border-radius: 50%;" /></a></div>';
									}

									$sender_names[] = '<a href="' . esc_url( bp_core_get_user_domain( $sender_id ) ) . '" target="_blank" rel="nofollow" style="color: ' . esc_attr( $settings['body_secondary_text_color'] ) . '!important; font-weight: 500; text-decoration: none;">' . esc_html( bp_core_get_user_displayname( $sender_id ) ) . '</a>';
									$avatars_iteration++;
								}
								?>
								<tr>
									<td valign="middle" width="65px" style="vertical-align: middle;">
										<?php echo implode( ' ', $sender_avatars ); ?>
									</td>
									<td width="88%" style="vertical-align: middle;">
										<div style="font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: <?php echo esc_attr( $settings['body_text_size'] . 'px' ); ?>; line-height: <?php echo esc_attr( $settings['body_text_size'] . 'px' ); ?>; letter-spacing: -0.24px;">
											<?php echo implode( ', ', $sender_names ); ?>
										</div>
									</td>
								</tr>
								<?php
							}
							?>
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
					<table cellspacing="0" cellpadding="0" border="0" width="100%" style="background: <?php echo esc_attr( $settings['quote_bg'] ); ?>; border: 1px solid <?php echo esc_attr( $settings['body_border_color'] ); ?>; border-radius: 4px; border-collapse: separate !important">
						<tbody>
						<tr>
							<td height="30px" style="font-size: 25px; line-height: 25px;">&nbsp;</td>
						</tr>
						<tr>
							<td align="center">
								<table cellpadding="0" cellspacing="0" border="0" width="86%" style="width: 86%;">
									<tbody>
									<?php
									$total_messages = count( $tokens['message'] );
									$message_index  = 1;
									foreach ( $tokens['message'] as $message ) {
										?>
										<tr style="<?php echo ( $total_messages !== $message_index ) ? 'border-bottom: 1px solid' . esc_attr( $settings['body_border_color'] ) . ';' : ''; ?>">
											<td valign="middle" width="55px" style="vertical-align: top;">
												<a style="display: block; width: 40px;" href="<?php echo esc_url( bp_core_get_user_domain( $message['sender_id'] ) ); ?>" target="_blank" rel="nofollow">
													<?php
													$avatar_url = bp_core_fetch_avatar(
														array(
															'item_id' => $message['sender_id'],
															'width' => 100,
															'height' => 100,
															'type' => 'full',
															'html' => false,
														)
													);
													?>
													<img alt="" src="<?php echo esc_url( $avatar_url ); ?>" width="40" height="40" border="0" style="margin:0; padding:0; border:none; display:block; width: 40px; height: 40px; border-radius: 50%;" />
												</a>
											</td>
											<td width="88%" style="vertical-align: top;padding-bottom:20px;">
												<p style="margin:0 0 5px 0;">
													<a href="<?php echo esc_url( bp_core_get_user_domain( $message['sender_id'] ) ); ?>" target="_blank" rel="nofollow" style="color: <?php echo esc_attr( $settings['body_secondary_text_color'] ); ?>!important; font-weight: 500; text-decoration:none;"><?php echo esc_html( bp_core_get_user_displayname( $message['sender_id'] ) ); ?></a>
												</p>
												<div class="bb-email-message-content" style="font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: <?php echo esc_attr( $settings['body_text_size'] . 'px' ); ?>; letter-spacing: -0.24px; line-height: <?php echo esc_attr( floor( $settings['body_text_size'] * 1.625 ) . 'px' ); ?>;">
													<?php echo stripslashes( wpautop( $message['message'] ) ); ?>
												</div>
												<?php
												$media_ids       = '';
												$total_media_ids = 0;
												if ( bp_is_active( 'media' ) && bp_is_messages_media_support_enabled() && ! empty( $message['message_id'] ) ) {
													$media_ids = bp_messages_get_meta( $message['message_id'], 'bp_media_ids', true );

													if ( ! empty( $media_ids ) ) {
														$media_ids       = explode( ',', $media_ids );
														$total_media_ids = count( $media_ids );
														$media_ids       = implode( ',', array_slice( $media_ids, 0, 5 ) );
													}
												}

												$video_ids       = '';
												$total_video_ids = 0;
												if ( bp_is_active( 'media' ) && bp_is_messages_video_support_enabled() && ! empty( $message['message_id'] ) ) {
													$video_ids = bp_messages_get_meta( $message['message_id'], 'bp_video_ids', true );

													if ( ! empty( $video_ids ) ) {
														$video_ids       = explode( ',', $video_ids );
														$total_video_ids = count( $video_ids );
														$video_ids       = implode( ',', array_slice( $video_ids, 0, 5 ) );
													}
												}

												$document_ids       = '';
												$total_document_ids = 0;
												if ( bp_is_active( 'media' ) && bp_is_messages_document_support_enabled() && ! empty( $message['message_id'] ) ) {
													$document_ids = bp_messages_get_meta( $message['message_id'], 'bp_document_ids', true );

													if ( ! empty( $document_ids ) ) {
														$document_ids       = explode( ',', $document_ids );
														$total_document_ids = count( $document_ids );
														$document_ids       = implode( ',', array_slice( $document_ids, 0, 5 ) );
													}
												}

												$gif_data = array();
												if ( bp_is_active( 'media' ) && bp_is_messages_gif_support_enabled() && ! empty( $message['message_id'] ) ) {
													$gif_data = bp_messages_get_meta( $message['message_id'], '_gif_data', true );
												}

												if ( ! empty( $media_ids ) && bp_has_media(
													array(
														'include' => $media_ids,
														'order_by' => 'menu_order',
														'sort' => 'ASC',
													)
												) ) :
													?>
													<div class="bb-activity-media-wrap" style="padding: 10px 0; width: 250px; height: 200px;">
														<?php
														while ( bp_media() ) {
															bp_the_media();

															$attachment_id = base64_encode( 'forbidden_' . bp_get_media_attachment_id() );
															$media_url     = home_url( '/' ) . 'bb-attachment-media-preview/' . $attachment_id . '/bb-media-activity-image/' . base64_encode( 'thread_' . $message['thread_id'] );
															?>
															<div class="bb-activity-media-elem"  style="width: 250px; vertical-align: top; height: 200px; overflow: hidden;padding:0;">
																<a href="<?php echo esc_url( $tokens['message.url'] ); ?>">
																	<img style="border-radius: 4px; min-width: 100%; min-height: 100%; max-width: 100%; object-fit: cover;" src="<?php echo esc_url( $media_url ); ?>" alt="<?php echo esc_attr( bp_get_media_title() ); ?>" />
																</a>
															</div>
															<?php if ( $total_media_ids > 1 ) : ?>
																<p style="height: 6px;border-radius: 0px 0px 4px 4px;max-width: 240px;margin: 0;margin-left: 5px;width:100%;background-color: #b5b7bb;padding:0;"></p>
																<p style="height: 6px;border-radius: 0px 0px 4px 4px;max-width: 222px;margin: 0;margin-left: 14px;width:100%;background-color: #e1e4e8;padding:0;"></p>
															<?php endif; ?>
															<?php
															break;
														}
														?>
													</div>
													<?php
												endif;

												if ( ! empty( $video_ids ) && bp_has_video(
													array(
														'include' => $video_ids,
														'order_by' => 'menu_order',
														'sort' => 'ASC',
													)
												) ) :
													?>
													<div class="bb-activity-media-wrap" style="padding: 10px 0; width: 250px;">
														<?php
														while ( bp_video() ) {
															bp_the_video();

															$poster_thumb = bp_get_video_activity_thumb();
															if ( empty( $poster_thumb ) ) {
																$poster_thumb = bp_get_video_popup_thumb();
															}
															if ( empty( $poster_thumb ) ) {
																$poster_thumb = bb_get_video_default_placeholder_image();
															}
															?>
															<div class="bb-activity-media-elem" style="background-image: url('<?php echo esc_url( $poster_thumb ); ?>'); background-size:cover; display: block; width: 250px; vertical-align: top; height: 145px; overflow: hidden; padding: 0; border-radius: 4px;padding:0;">
																<a href="<?php echo esc_url( $tokens['message.url'] ); ?>">
																	<img style="display: block; height: 60px;width: 60px; background-color: #fff; border-radius: 50%; margin: 42.5px 0 0 95px" src="<?php echo esc_url( buddypress()->plugin_url ); ?>bp-templates/bp-nouveau/images/video-play.svg" alt="<?php echo esc_attr( bp_get_video_title() ); ?>" />
																</a>
															</div>
															<?php if ( $total_video_ids > 1 ) : ?>
																<p style="height: 6px;border-radius: 0px 0px 4px 4px;max-width: 240px;margin: 0;margin-left: 5px;width:100%;background-color: #b5b7bb;padding:0;"></p>
																<p style="height: 6px;border-radius: 0px 0px 4px 4px;max-width: 222px;margin: 0;margin-left: 14px;width:100%;background-color: #e1e4e8;padding:0;"></p>
															<?php endif; ?>
															<?php
															break;
														}
														?>
													</div>
													<?php
												endif;

												if (
													! empty( $document_ids ) &&
													bp_has_document(
														array(
															'include'  => $document_ids,
															'order_by' => 'menu_order',
															'sort'     => 'ASC',
															'per_page' => 5,
														)
													)
												) :
													?>
													<div class="bb-activity-media-wrap" style="padding: 10px 0;">
														<?php
														while ( bp_document() ) {
															bp_the_document();

															$attachment_id = bp_get_document_attachment_id();
															$filename      = basename( get_attached_file( $attachment_id ) );
															$size          = is_file( get_attached_file( $attachment_id ) ) ? bp_document_size_format( filesize( get_attached_file( $attachment_id ) ) ) : 0;
															$extension     = bp_get_document_extension();
															?>
															<div class="bb-activity-media-elem">
																<a href="<?php echo esc_url( $tokens['message.url'] ); ?>" style="font-size:14px; text-decoration:none;">
																	<span style="font-weight:500;"><?php echo esc_html( $filename ); ?></span>
																	<span style="font-size: 13px; margin-left:5px; color: <?php echo esc_attr( $settings['body_text_color'] ); ?>;"><?php echo esc_html( strtolower( $size ) ); ?></span>
																	<span style="font-size: 13px; margin-left:3px; text-transform: uppercase; color: <?php echo esc_attr( $settings['body_text_color'] ); ?>;"><?php echo $extension ? esc_attr( $extension ) : ''; ?></span>
																</a>
															</div>
															<?php
														}
														?>
														<?php if ( $total_document_ids > 5 ) : ?>
															<a href="<?php echo esc_url( $tokens['message.url'] ); ?>"><?php echo sprintf( __( 'and %d more', 'buddyboss' ), $total_document_ids - 5 ); ?></a>
														<?php endif; ?>
													</div>
													<?php
												endif;

												if ( ! empty( $gif_data ) ) :
													?>
													<div class="activity-attached-gif-container">
														<div class="gif-image-container">
															<a href="<?php echo esc_url( $tokens['message.url'] ); ?>" class="gif-play-button">
																<?php if ( is_int( $gif_data['still'] ) ) { ?>
																	<img style="max-width: 250px;max-height: 185px;object-fit: cover;border-radius: 4px;" alt="" src="<?php echo esc_url( wp_get_attachment_url( $gif_data['still'] ) ); ?>" />
																<?php } else { ?>
																	<img style="max-width: 250px;max-height: 185px;object-fit: cover;border-radius: 4px;" alt="" src="<?php echo esc_url( $gif_data['still'] ); ?>" />
																<?php } ?>
															</a>
														</div>
													</div>
													<?php
												endif;
												?>
											</td>
										</tr>
										<tr>
											<td height="20px" style="font-size: 25px; line-height: 25px;">&nbsp;</td>
										</tr>
										<?php
										$message_index++;
									}
									?>
									</tbody>
								</table>
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
					<a href="<?php echo esc_url( $tokens['message.url'] ); ?>" target="_blank" rel="nofollow" style="font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: 14px; color: <?php echo esc_attr( $settings['highlight_color'] ); ?>; text-decoration: none; display: inline-block; border: 1px solid <?php echo esc_attr( $settings['highlight_color'] ); ?>; border-radius: 100px; text-align: center; height: 16px; line-height: 16px; padding: 10px 28px;"><?php esc_html_e( 'View Conversation', 'buddyboss' ); ?></a>
				</td>
			</tr>
		</table>
		<div class="spacer" style="font-size: 10px; line-height: 10px; height: 10px;">&nbsp;</div>
		<?php
		$output = str_replace( array( "\r", "\n" ), '', ob_get_contents() );
		ob_end_clean();

		return $output;
	}

	/**
	 * Generate the output for token delay message
	 *
	 * @since BuddyBoss 2.1.4
	 *
	 * @param \BP_Email $bp_email         Core component classes.
	 * @param array     $formatted_tokens Formatted token array.
	 * @param array     $tokens           Token array.
	 *
	 * @return string html for the output.
	 */
	public function token__sender_name( $bp_email, $formatted_tokens, $tokens ) {
		$output    = $tokens['sender.name'] ?? '';
		$bp_prefix = bp_core_get_table_prefix();

		if ( ! in_array( $bp_email->get( 'type' ), array( 'group-message-email', 'messages-unread' ), true ) || ! isset( $tokens['message_id'] ) ) {
			return $output;
		}

		$settings = bp_email_get_appearance_settings();

		global $wpdb;

		$table_name = $bp_prefix . 'bp_messages_messages';
		$sender_id  = $wpdb->get_var(
			$wpdb->prepare(
				'SELECT sender_id FROM `' . $table_name . '` WHERE id = %d',
				$tokens['message_id']
			)
		);

		if ( ! empty( $sender_id ) ) {
			$output = '<a href="' . esc_url( bp_core_get_user_domain( $sender_id ) ) . '" target="_blank" rel="nofollow" style="color: ' . esc_attr( $settings['highlight_color'] ) . '!important; font-weight: 500; text-decoration: none;">' . esc_html( bp_core_get_user_displayname( $sender_id ) ) . '</a>';
		}

		return $output;
	}

	/**
	 * Generate the output for token delay message
	 *
	 * @since BuddyBoss 2.1.4
	 *
	 * @param \BP_Email $bp_email         Core component classes.
	 * @param array     $formatted_tokens Formatted token array.
	 * @param array     $tokens           Token array.
	 *
	 * @return string html for the output.
	 */
	public function token__group_name( $bp_email, $formatted_tokens, $tokens ) {
		$output = $tokens['group.name'] ?? '';

		if ( ! in_array( $bp_email->get( 'type' ), array( 'group-message-email', 'group-message-digest' ), true ) ) {
			return $output;
		}

		$group = $tokens['group'] ?? false;
		if ( empty( $group ) ) {
			$group_id = $tokens['group.id'] ?? false;
			if ( empty( $group_id ) ) {
				$message_id = $tokens['message_id'] ?? false;
				$group_id   = bp_messages_get_meta( $message_id, 'group_id', true ); // group id.
			}

			if ( empty( $group_id ) ) {
				return $output;
			}

			$group = groups_get_group( $group_id );
		}

		if ( empty( $group ) ) {
			return $output;
		}

		$settings = bp_email_get_appearance_settings();

		$output = '<a href="' . esc_url( bp_get_group_permalink( $group ) ) . '" target="_blank" rel="nofollow" style="color: ' . esc_attr( $settings['highlight_color'] ) . '!important; font-weight: 500; text-decoration: none;">' . esc_html( bp_get_group_name( $group ) ) . '</a>';

		return $output;
	}

	/**
	 * Generate the output for token delay message
	 *
	 * @since BuddyBoss 2.1.4
	 *
	 * @param \BP_Email $bp_email         Core component classes.
	 * @param array     $formatted_tokens Formatted token array.
	 * @param array     $tokens           Token array.
	 *
	 * @return string html for the output.
	 */
	public function token__unread_count( $bp_email, $formatted_tokens, $tokens ) {
		$output = $tokens['unread.count'] ?? '';

		if ( ! in_array( $bp_email->get( 'type' ), array( 'messages-unread-digest', 'group-message-digest' ), true ) ) {
			return $output;
		}

		$settings = bp_email_get_appearance_settings();

		if ( isset( $tokens['message.url'], $tokens['unread.count'] ) && ! empty( $tokens['message.url'] ) ) {
			$output = '<a href="' . esc_url( $tokens['message.url'] ) . '" target="_blank" rel="nofollow" style="color: ' . esc_attr( $settings['highlight_color'] ) . '!important; font-weight: 500; text-decoration: none;">' . esc_html( $tokens['unread.count'] ) . '</a>';
		}

		return $output;
	}

	/**
	 * Strip all tags from the subject.
	 *
	 * @since BuddyBoss 2.1.4
	 *
	 * @param string $retval Property value.
	 * @param string $property_name Property name.
	 * @param string $transform How to transform the return value.
	 *                          Accepts 'raw' (default) or 'replace-tokens'.
	 *
	 * @return string The subject output without tags for subject.
	 */
	public function bb_email_subject_strip_all_tags( $retval, $property_name, $transform ) {

		if ( 'subject' === $property_name && 'replace-tokens' === $transform ) {
			return wp_strip_all_tags( $retval );
		}

		return $retval;
	}

	/**
	 * Generate the output for token activity.content
	 *
	 * @since BuddyBoss 2.2.3
	 *
	 * @param \BP_Email $bp_email
	 * @param array     $formatted_tokens
	 * @param array     $tokens
	 *
	 * @return string html for the output
	 */
	public function token__activity_content( $bp_email, $formatted_tokens, $tokens ) {
		global $bp;
		$output   = '';
		$settings = bp_email_get_appearance_settings();
		$activity = isset( $tokens['activity'] ) ? $tokens['activity'] : '';

		if ( 'groups-new-activity' === $bp_email->get( 'type' ) ) {
			return $this->token__group_activity_content( $bp_email, $formatted_tokens, $tokens );
		}

		if (
			empty( $activity ) ||
			in_array( $activity->privacy, array( 'document', 'media', 'video' ), true )
		) {
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
								<a style="display: block; width: 47px;" href="<?php echo esc_url( bp_core_get_user_domain( $activity->user_id ) ); ?>" target="_blank" rel="nofollow">
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
									<img alt="" src="<?php echo esc_url( $avatar_url ); ?>" width="47" height="47" border="0" style="margin:0; padding:0; border:none; display:block; max-width: 47px; border-radius: 50%;"/>
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
					<table cellspacing="0" cellpadding="0" border="0" width="100%" style="background: <?php echo esc_attr( $settings['quote_bg'] ); ?>;">
						<tbody>
						<tr>
							<td>
								<table cellspacing="0" cellpadding="0" border="0" width="100%" style="background: <?php echo esc_attr( $settings['quote_bg'] ); ?>; border: 1px solid <?php echo esc_attr( $settings['body_border_color'] ); ?>; border-radius: 4px; border-collapse: separate !important">
									<tbody>
									<tr>
										<td height="15px" style="font-size: 15px; line-height: 15px;">&nbsp;</td>
									</tr>
									<tr>
										<td align="center">
											<table cellpadding="0" cellspacing="0" border="0" width="86%" style="width: 86%;">
												<tbody>
												<tr>
													<td width="88%" style="vertical-align: top;">
														<div class="bb-email-activity-content" style="font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: <?php echo esc_attr( $settings['body_text_size'] . 'px' ); ?>; letter-spacing: -0.24px; line-height: <?php echo esc_attr( floor( $settings['body_text_size'] * 1.625 ) . 'px' ); ?>;">
															<?php
															if ( in_array( $activity->content, array( '&nbsp;', '&#8203;' ), true ) ) {
																$activity->content = '';
															}
															// Check if link embed or link preview and append the content accordingly.
															$link_embed = bp_activity_get_meta( $activity->id, '_link_embed', true );
															if ( empty( preg_replace( '/(?:<p>\s*<\/p>\s*)+|<p>(\s|(?:<br>|<\/br>|<br\/?>))*<\/p>/', '', $activity->content ) ) && ! empty( $link_embed ) ) {
																$activity->content .= $link_embed;
															}

															$removed_autoembed_filter = false;
															if (
																function_exists( 'bp_use_embed_in_activity' ) &&
																bp_use_embed_in_activity() &&
																method_exists( $bp->embed, 'autoembed' ) &&
																method_exists( $bp->embed, 'run_shortcode' )
															) {
																$removed_autoembed_filter = true;
																remove_filter( 'bp_get_activity_content_body', array( $bp->embed, 'autoembed' ), 8, 2 );
																remove_filter( 'bp_get_activity_content_body', array( $bp->embed, 'run_shortcode' ), 7, 2 );
															}

															// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
															echo apply_filters_ref_array( 'bp_get_activity_content_body', array( $activity->content, &$activity ) );

															if ( $removed_autoembed_filter ) {
																add_filter( 'bp_get_activity_content_body', array( $bp->embed, 'autoembed' ), 8, 2 );
																add_filter( 'bp_get_activity_content_body', array( $bp->embed, 'run_shortcode' ), 7, 2 );
															}
															?>
														</div>
														<?php
														$media_ids       = '';
														$total_media_ids = 0;

														if ( bp_is_active( 'media' ) && bp_is_profile_media_support_enabled() ) {
															$media_ids = bp_activity_get_meta( $activity->id, 'bp_media_ids', true );

															if ( ! empty( $media_ids ) ) {
																$media_ids       = explode( ',', $media_ids );
																$total_media_ids = count( $media_ids );
																$media_ids       = implode( ',', array_slice( $media_ids, 0, 5 ) );
															}
														}

														$video_ids       = '';
														$total_video_ids = 0;
														if ( bp_is_active( 'media' ) && bp_is_profile_video_support_enabled() ) {
															$video_ids = bp_activity_get_meta( $activity->id, 'bp_video_ids', true );

															if ( ! empty( $video_ids ) ) {
																$video_ids       = explode( ',', $video_ids );
																$total_video_ids = count( $video_ids );
																$video_ids       = implode( ',', array_slice( $video_ids, 0, 5 ) );
															}
														}

														$document_ids       = '';
														$total_document_ids = 0;
														if ( bp_is_active( 'media' ) && bp_is_profile_document_support_enabled() ) {
															$document_ids = bp_activity_get_meta( $activity->id, 'bp_document_ids', true );

															if ( ! empty( $document_ids ) ) {
																$document_ids       = explode( ',', $document_ids );
																$total_document_ids = count( $document_ids );
																$document_ids       = implode( ',', array_slice( $document_ids, 0, 5 ) );
															}
														}

														$gif_data = array();
														if ( bp_is_active( 'media' ) && bp_is_profiles_gif_support_enabled() ) {
															$gif_data = bp_activity_get_meta( $activity->id, '_gif_data', true );
														}

														if (
															! empty( $media_ids ) &&
															bp_has_media(
																array(
																	'include'  => $media_ids,
																	'order_by' => 'menu_order',
																	'sort'     => 'ASC',
																)
															)
														) {
															?>
															<div class="bb-activity-media-wrap" style="padding: 15px 0; width: 250px; height: 200px;">
																<?php
																while ( bp_media() ) {
																	bp_the_media();

																	$media_id      = 'forbidden_' . bp_get_media_id();
																	$attachment_id = 'forbidden_' . bp_get_media_attachment_id();
																	$media_url     = home_url( '/' ) . 'bb-media-preview/' . base64_encode( $attachment_id ) . '/' . base64_encode( $media_id );
																	?>
																	<div class="bb-activity-media-elem" style="width: 250px; vertical-align: top; height: 200px; overflow: hidden;padding:0;">
																		<a href="<?php echo esc_url( $tokens['activity.url'] ); ?>">
																			<img style="border-radius: 4px; min-width: 100%; min-height: 100%; max-width: 100%; object-fit: cover;" src="<?php echo esc_url( $media_url ); ?>" alt="<?php echo esc_attr( bp_get_media_title() ); ?>"/>
																		</a>
																	</div>
																	<?php if ( $total_media_ids > 1 ) : ?>
																		<p style="height: 6px;border-radius: 0px 0px 4px 4px;max-width: 240px;margin: 0;margin-left: 5px;width:100%;background-color: #b5b7bb;padding:0;"></p>
																		<p style="height: 6px;border-radius: 0px 0px 4px 4px;max-width: 222px;margin: 0;margin-left: 14px;width:100%;background-color: #e1e4e8;padding:0;"></p>
																	<?php endif; ?>
																	<?php
																	break;
																}
																?>
															</div>
															<?php
														}

														if (
															! empty( $video_ids ) &&
															bp_has_video(
																array(
																	'include'  => $video_ids,
																	'order_by' => 'menu_order',
																	'sort'     => 'ASC',
																)
															)
														) {
															?>
															<div class="bb-activity-media-wrap" style="padding: 15px 0; width: 250px;">
																<?php
																while ( bp_video() ) {
																	bp_the_video();
																	$poster_thumb = bp_get_video_activity_thumb();
																	if ( empty( $poster_thumb ) ) {
																		$poster_thumb = bp_get_video_popup_thumb();
																	}
																	if ( empty( $poster_thumb ) ) {
																		$poster_thumb = bb_get_video_default_placeholder_image();
																	}
																	?>
																	<div class="bb-activity-media-elem" style="background-image: url('<?php echo esc_url( $poster_thumb ); ?>'); background-size:cover; display: block; width: 250px; vertical-align: top; height: 145px; overflow: hidden; padding: 0; border-radius: 4px;padding:0;">
																		<a href="<?php echo esc_url( $tokens['activity.url'] ); ?>">
																			<img style="display: block; height: 60px;width: 60px; background-color: #fff; border-radius: 50%; margin: 42.5px 0 0 95px" src="<?php echo esc_url( buddypress()->plugin_url ); ?>bp-templates/bp-nouveau/images/video-play.svg" alt="<?php echo esc_attr( bp_get_video_title() ); ?>"/>
																		</a>
																	</div>
																	<?php if ( $total_video_ids > 1 ) : ?>
																		<p style="height: 6px;border-radius: 0px 0px 4px 4px;max-width: 240px;margin: 0;margin-left: 5px;width:100%;background-color: #b5b7bb;padding:0;"></p>
																		<p style="height: 6px;border-radius: 0px 0px 4px 4px;max-width: 222px;margin: 0;margin-left: 14px;width:100%;background-color: #e1e4e8;padding:0;"></p>
																		<?php
																	endif;

																	break;
																}
																?>
															</div>
															<?php
														}

														if (
															! empty( $document_ids ) &&
															bp_has_document(
																array(
																	'include'  => $document_ids,
																	'order_by' => 'menu_order',
																	'sort'     => 'ASC',
																	'per_page' => 5,
																)
															)
														) {
															?>
															<div class="bb-activity-media-wrap" style="padding: 15px 0 15px 0; width: 250px;">
																<?php
																while ( bp_document() ) {
																	bp_the_document();
																	$attachment_id = bp_get_document_attachment_id();
																	$filename      = basename( get_attached_file( $attachment_id ) );
																	$size          = is_file( get_attached_file( $attachment_id ) ) ? bp_document_size_format( filesize( get_attached_file( $attachment_id ) ) ) : 0;
																	$extension     = bp_get_document_extension();
																	?>
																	<div class="bb-activity-media-elem" style="width:100%">
																		<a href="<?php echo esc_url( $tokens['activity.url'] ); ?>" style="font-size:14px; text-decoration:none;">
																			<span style="font-weight:500;"><?php echo esc_html( $filename ); ?></span>
																			<span style="font-size: 13px; margin-left:5px; color: <?php echo esc_attr( $settings['body_text_color'] ); ?>;"><?php echo esc_html( strtolower( $size ) ); ?></span>
																			<span style="font-size: 13px; margin-left:3px; text-transform: uppercase; color: <?php echo esc_attr( $settings['body_text_color'] ); ?>;"><?php echo $extension ? esc_attr( $extension ) : ''; ?></span>
																		</a>
																	</div>
																	<?php
																}
																?>
																<?php if ( $total_document_ids > 5 ) : ?>
																	<a href=""><?php echo sprintf( __( 'and %d more', 'buddyboss' ), $total_document_ids - 5 ); ?></a>
																<?php endif; ?>
															</div>
															<?php
														}

														if ( ! empty( $gif_data ) ) {
															?>
															<div style="padding: 15px 0;">
																<div>
																	<a href="<?php echo esc_url( $tokens['activity.url'] ); ?>" class="gif-play-button">
																		<?php if ( is_int( $gif_data['still'] ) ) { ?>
																			<img style="max-width: 250px;max-height: 185px;object-fit: cover;border-radius: 4px;" alt="" src="<?php echo esc_url( wp_get_attachment_url( $gif_data['still'] ) ); ?>"/>
																		<?php } else { ?>
																			<img style="max-width: 250px;max-height: 185px;object-fit: cover;border-radius: 4px;" alt="" src="<?php echo esc_url( $gif_data['still'] ); ?>"/>
																		<?php } ?>
																	</a>
																</div>
															</div>
															<?php
														}
														?>
													</td>
												</tr>
												<tr>
													<td height="15px" style="font-size: 15px; line-height: 15px;">&nbsp;</td>
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
				<td height="24px" style="font-size: 24px; line-height: 24px;">&nbsp;</td>
			</tr>

			<tr>
				<td><a href="<?php echo esc_url( $tokens['activity.url'] ); ?>" target="_blank" rel="nofollow"
					   style="font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: 14px; color: <?php echo esc_attr( $settings['highlight_color'] ); ?>; text-decoration: none; display: inline-block; border: 1px solid <?php echo esc_attr( $settings['highlight_color'] ); ?>; border-radius: 100px; min-width: 64px; text-align: center; height: 20px; line-height: 20px; padding: 9px 18px;"><?php esc_html_e( 'View Post', 'buddyboss' ); ?></a></td>
			</tr>
		</table>
		<div class="spacer" style="font-size: 10px; line-height: 10px; height: 10px;">&nbsp;</div>
		<?php
		$output = str_replace( array( "\r", "\n" ), '', ob_get_clean() );

		return $output;
	}

	/**
	 * Generate the output for token group discussion.content.
	 *
	 * @since BuddyBoss 2.2.8
	 *
	 * @param \BP_Email $bp_email         Core component classes.
	 * @param array     $formatted_tokens Formatted token array.
	 * @param array     $tokens           Token array.
	 *
	 * @return string html for the output
	 */
	public function token__group_discussion_content( $bp_email, $formatted_tokens, $tokens ) {
		$output = '';

		$settings = bp_email_get_appearance_settings();

		$media_ids       = '';
		$total_media_ids = 0;
		if ( bp_is_active( 'media' ) && bp_is_group_media_support_enabled() && ! empty( $tokens['discussion.id'] ) ) {
			$media_ids = get_post_meta( $tokens['discussion.id'], 'bp_media_ids', true );
			if ( ! empty( $media_ids ) ) {
				$media_ids       = explode( ',', $media_ids );
				$total_media_ids = count( $media_ids );
				$media_ids       = implode( ',', array_slice( $media_ids, 0, 5 ) );
			}
		}

		$video_ids       = '';
		$total_video_ids = 0;
		if ( bp_is_active( 'media' ) && bp_is_group_video_support_enabled() && ! empty( $tokens['discussion.id'] ) ) {
			$video_ids = get_post_meta( $tokens['discussion.id'], 'bp_video_ids', true );
			if ( ! empty( $video_ids ) ) {
				$video_ids       = explode( ',', $video_ids );
				$total_video_ids = count( $video_ids );
				$video_ids       = implode( ',', array_slice( $video_ids, 0, 5 ) );
			}
		}

		$document_ids       = '';
		$total_document_ids = 0;
		if ( bp_is_active( 'media' ) && bp_is_group_document_support_enabled() && ! empty( $tokens['discussion.id'] ) ) {
			$document_ids = get_post_meta( $tokens['discussion.id'], 'bp_document_ids', true );
			if ( ! empty( $document_ids ) ) {
				$document_ids       = explode( ',', $document_ids );
				$total_document_ids = count( $document_ids );
				$document_ids       = implode( ',', array_slice( $document_ids, 0, 5 ) );
			}
		}

		$gif_data = array();
		if ( bp_is_active( 'media' ) && bp_is_groups_gif_support_enabled() && ! empty( $tokens['discussion.id'] ) ) {
			$gif_data = get_post_meta( $tokens['discussion.id'], '_gif_data', true );
		}

		ob_start();
		?>
		<div class="spacer" style="font-size: 5px; line-height: 5px; height: 5px;">&nbsp;</div>
		<table cellspacing="0" cellpadding="0" border="0" width="100%">

			<tr>
				<td height="5px" style="font-size: 24px; line-height: 5px;">&nbsp;</td>
			</tr>
			<tr>
				<td>
					<div style="color: <?php echo esc_attr( $settings['body_secondary_text_color'] ); ?>; font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: <?php echo esc_attr( $settings['body_text_size'] . 'px' ); ?>; font-weight: 500; letter-spacing: -0.24px; line-height: <?php echo esc_attr( floor( $settings['body_text_size'] * 1.625 ) . 'px' ); ?>;">
						<?php echo wp_kses_post( $formatted_tokens['discussion.title'] ); ?>
					</div>
				</td>
			</tr>
			<tr>
				<td height="24px" style="font-size: 24px; line-height: 24px;">&nbsp;</td>
			</tr>
			<tr>
				<td align="center">
					<table cellpadding="0" cellspacing="0" border="0" width="100%">
						<tbody>
						<tr>
							<td valign="middle" width="65px" style="vertical-align: middle;">
								<a style="display: block; width: 47px;" href="<?php echo esc_url( bp_core_get_user_domain( bbp_get_topic_author_id( $formatted_tokens['discussion.id'] ) ) ); ?>" target="_blank" rel="nofollow">
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
									<img src="<?php echo esc_url( $avatar_url ); ?>" width="47" height="47" border="0" style="margin:0; padding:0; border:none; display:block; max-width: 47px; border-radius: 50%;" />
								</a>
							</td>
							<td width="88%" style="vertical-align: middle;">
								<div style="color: <?php echo esc_attr( $settings['body_secondary_text_color'] ); ?>; font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: <?php echo esc_attr( $settings['body_text_size'] . 'px' ); ?>; line-height: <?php echo esc_attr( $settings['body_text_size'] . 'px' ); ?>; letter-spacing: -0.24px;"><?php echo wp_kses_post( bbp_get_topic_author_display_name( $formatted_tokens['discussion.id'] ) ); ?></div>
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
					<table cellspacing="0" cellpadding="0" border="0" width="100%" style="background: <?php echo esc_attr( $settings['quote_bg'] ); ?>; border: 1px solid <?php echo esc_attr( $settings['body_border_color'] ); ?>; border-radius: 4px; border-collapse: separate !important">
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

									<?php
									if ( ! empty( $media_ids ) && bp_has_media(
										array(
											'include'  => $media_ids,
											'order_by' => 'menu_order',
											'sort'     => 'ASC',
											'privacy'  => false,
										)
									) ) :
										?>
										<tr>
											<td>
												<div class="bb-activity-media-wrap" style="padding: 5px 0 10px;">
												<?php
												while ( bp_media() ) {
													bp_the_media();

													$media_id      = 'forbidden_' . bp_get_media_id();
													$attachment_id = 'forbidden_' . bp_get_media_attachment_id();
													$media_url     = home_url( '/' ) . 'bb-media-preview/' . base64_encode( $attachment_id ) . '/' . base64_encode( $media_id ) . '/bb-media-activity-image';
													?>
													<div class="bb-activity-media-elem"  style="width: 250px; vertical-align: top; height: 200px; overflow: hidden;">
														<a href="<?php echo esc_url( $tokens['discussion.url'] ); ?>">
															<img style="border-radius: 4px; width:100%; height: 100%; object-fit: cover;" src="<?php echo esc_url( $media_url ); ?>" alt="<?php echo esc_attr( bp_get_media_title() ); ?>" />
														</a>
													</div>
													<?php if ( $total_media_ids > 1 ) : ?>
														<p style="height: 6px;background: rgba(0, 0, 0, 0.3);border-radius: 0px 0px 4px 4px;max-width: 240px;margin: 0;margin-left: 5px;"></p>
														<p style="height: 6px;background: rgba(0, 0, 0, 0.1);border-radius: 0px 0px 4px 4px;max-width: 222px;margin: 0;margin-left: 14px;"></p>
													<?php endif; ?>
													<?php
													break;
												}
												?>
											</div>
											</td>
										</tr>
										<?php
									endif;

									if ( ! empty( $video_ids ) && bp_has_video(
										array(
											'include'  => $video_ids,
											'order_by' => 'menu_order',
											'sort'     => 'ASC',
											'privacy'  => false,
										)
									) ) :
										?>
										<tr>
											<td>
												<div class="bb-activity-media-wrap" style="padding: 5px 0 10px;">
													<?php
													while ( bp_video() ) {
														bp_the_video();

														$poster_thumb = bp_get_video_activity_thumb();
														if ( empty( $poster_thumb ) ) {
															$poster_thumb = bp_get_video_popup_thumb();
														}
														if ( empty( $poster_thumb ) ) {
															$poster_thumb = bb_get_video_default_placeholder_image();
														}
														?>
														<div class="bb-activity-media-elem"  style="background-image: url('<?php echo esc_url( $poster_thumb ); ?>'); background-size:cover; display: block; width: 250px; vertical-align: top; height: 145px; overflow: hidden; padding: 0; border-radius: 4px;">
															<a href="<?php echo esc_url( $tokens['discussion.url'] ); ?>">
																<img style="display: block; height: 60px;width: 60px; background-color: #fff; border-radius: 50%; margin: 42.5px 0 0 95px" src="<?php echo esc_url( buddypress()->plugin_url ); ?>bp-templates/bp-nouveau/images/video-play.svg" alt="<?php echo esc_attr( bp_get_video_title() ); ?>" />
															</a>
														</div>
														<?php if ( $total_video_ids > 1 ) : ?>
															<p style="height: 6px;background: rgba(0, 0, 0, 0.3);border-radius: 0px 0px 4px 4px;max-width: 240px;margin: 0;margin-left: 5px;"></p>
															<p style="height: 6px;background: rgba(0, 0, 0, 0.1);border-radius: 0px 0px 4px 4px;max-width: 222px;margin: 0;margin-left: 14px;"></p>
														<?php endif; ?>
														<?php
														break;
													}
													?>
												</div>
											</td>
										</tr>
										<?php
									endif;

									if (
										! empty( $document_ids ) &&
										bp_has_document(
											array(
												'include'  => $document_ids,
												'order_by' => 'menu_order',
												'sort'     => 'ASC',
												'privacy'  => false,
												'per_page' => 5,
											)
										)
									) :
										?>
										<tr>
											<td>
												<div style="padding: 5px 0 10px;">
												<?php
												while ( bp_document() ) {
													bp_the_document();

													$attachment_id = bp_get_document_attachment_id();
													$filename      = basename( get_attached_file( $attachment_id ) );
													$size          = is_file( get_attached_file( $attachment_id ) ) ? bp_document_size_format( filesize( get_attached_file( $attachment_id ) ) ) : 0;
													$extension     = bp_get_document_extension();
													?>
													<div class="bb-activity-media-elem">
														<a href="<?php echo esc_url( $tokens['discussion.url'] ); ?>" style="font-size:14px; text-decoration:none;">
															<span style="font-weight:500;"><?php echo esc_html( $filename ); ?></span>
															<span style="font-size: 13px; margin-left:5px; color: <?php echo esc_attr( $settings['body_text_color'] ); ?>;"><?php echo esc_html( $size ); ?></span>
															<span style="font-size: 13px; margin-left:3px; text-transform: uppercase; color: <?php echo esc_attr( $settings['body_text_color'] ); ?>;"><?php echo $extension ? esc_attr( $extension ) : ''; ?></span>
														</a>
													</div>
													<?php
												}

												if ( $total_document_ids > 5 ) :
													?>
													<a href="<?php echo esc_url( $tokens['discussion.url'] ); ?>">
														<?php
														echo sprintf(
														/* translators: The more documents. */
															__( 'and %d more', 'buddyboss' ),
															$total_document_ids - 5
														);
														?>
													</a>
												<?php endif; ?>
											</div>
											</td>
										</tr>
										<?php
									endif;

									if ( ! empty( $gif_data ) ) :
										?>
									<tr>
										<td>
											<div class="activity-attached-gif-container">
												<div class="gif-image-container">
													<a href="<?php echo esc_url( $tokens['discussion.url'] ); ?>">
														<?php if ( is_int( $gif_data['still'] ) ) { ?>
															<img style="max-width: 250px;max-height: 185px;object-fit: cover;border-radius: 4px;" alt="" src="<?php echo esc_url( wp_get_attachment_url( $gif_data['still'] ) ); ?>" />
														<?php } else { ?>
															<img style="max-width: 250px;max-height: 185px;object-fit: cover;border-radius: 4px;" alt="" src="<?php echo esc_url( $gif_data['still'] ); ?>" />
														<?php } ?>
													</a>
												</div>
											</div>
										</td>
									</tr>
									<?php endif; ?>
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
					<a href="<?php echo esc_url( $tokens['discussion.url'] ); ?>" target="_blank" rel="nofollow" style="font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: 14px; color: <?php echo esc_attr( $settings['highlight_color'] ); ?>; text-decoration: none; display: block; border: 1px solid <?php echo esc_attr( $settings['highlight_color'] ); ?>; border-radius: 100px; width: 150px; text-align: center; height: 16px; line-height: 16px; padding: 8px;"><?php esc_html_e( 'View Discussion', 'buddyboss' ); ?></a>
				</td>
			</tr>
		</table>
		<div class="spacer" style="font-size: 30px; line-height: 30px; height: 30px;">&nbsp;</div>
		<?php
		$output = str_replace( array( "\r", "\n" ), '', ob_get_clean() );

		return $output;
	}

	/**
	 * Generate the output for token group activity.content
	 *
	 * @since BuddyBoss 2.2.9.1
	 *
	 * @param \BP_Email $bp_email         Core component classes.
	 * @param array     $formatted_tokens Formatted token array.
	 * @param array     $tokens           Token array.
	 *
	 * @return string html for the output
	 */
	public function token__group_activity_content( $bp_email, $formatted_tokens, $tokens ) {
		global $bp;
		$output   = '';
		$settings = bp_email_get_appearance_settings();
		$activity = isset( $tokens['activity'] ) ? $tokens['activity'] : '';

		ob_start();
		?>
		<table cellspacing="0" cellpadding="0" border="0" width="100%">
			<tr>
				<td align="center">
					<table cellpadding="0" cellspacing="0" border="0" width="100%">
						<tbody>
							<tr>
								<td valign="middle" width="65px" style="vertical-align: middle;">
									<a style="display: block; width: 47px;" href="<?php echo esc_url( bp_core_get_user_domain( $activity->user_id ) ); ?>" target="_blank" rel="nofollow">
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
										<img alt="" src="<?php echo esc_url( $avatar_url ); ?>" width="47" height="47" border="0" style="margin:0; padding:0; border:none; display:block; max-width: 47px; border-radius: 50%;"/>
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
					<table cellspacing="0" cellpadding="0" border="0" width="100%" style="background: <?php echo esc_attr( $settings['quote_bg'] ); ?>;">
						<tbody>
						<tr>
							<td>
								<table cellspacing="0" cellpadding="0" border="0" width="100%" style="background: <?php echo esc_attr( $settings['quote_bg'] ); ?>; border: 1px solid <?php echo esc_attr( $settings['body_border_color'] ); ?>; border-radius: 4px; border-collapse: separate !important">
									<tbody>
									<tr>
										<td height="15px" style="font-size: 15px; line-height: 15px;">&nbsp;</td>
									</tr>
									<tr>
										<td align="center">
											<table cellpadding="0" cellspacing="0" border="0" width="86%" style="width: 86%;">
												<tbody>
												<tr>
													<td width="88%" style="vertical-align: top;">
														<div class="bb-email-activity-content" style="font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: <?php echo esc_attr( $settings['body_text_size'] . 'px' ); ?>; letter-spacing: -0.24px; line-height: <?php echo esc_attr( floor( $settings['body_text_size'] * 1.625 ) . 'px' ); ?>;">
															<?php
															if ( in_array( $activity->content, array( '&nbsp;', '&#8203;' ), true ) ) {
																$activity->content = '';
															}
															// Check if link embed or link preview and append the content accordingly.
															$link_embed = bp_activity_get_meta( $activity->id, '_link_embed', true );
															if ( empty( preg_replace( '/(?:<p>\s*<\/p>\s*)+|<p>(\s|(?:<br>|<\/br>|<br\/?>))*<\/p>/', '', $activity->content ) ) && ! empty( $link_embed ) ) {
																$activity->content .= $link_embed;
															}

															$removed_autoembed_filter = false;
															if (
																function_exists( 'bp_use_embed_in_activity' ) &&
																bp_use_embed_in_activity() &&
																method_exists( $bp->embed, 'autoembed' ) &&
																method_exists( $bp->embed, 'run_shortcode' )
															) {
																$removed_autoembed_filter = true;
																remove_filter( 'bp_get_activity_content_body', array( $bp->embed, 'autoembed' ), 8, 2 );
																remove_filter( 'bp_get_activity_content_body', array( $bp->embed, 'run_shortcode' ), 7, 2 );
															}

															// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
															echo apply_filters_ref_array( 'bp_get_activity_content_body', array( $activity->content, &$activity ) );

															if ( $removed_autoembed_filter ) {
																add_filter( 'bp_get_activity_content_body', array( $bp->embed, 'autoembed' ), 8, 2 );
																add_filter( 'bp_get_activity_content_body', array( $bp->embed, 'run_shortcode' ), 7, 2 );
															}
															?>
														</div>
														<?php
														$media_ids       = '';
														$total_media_ids = 0;
														if ( bp_is_active( 'media' ) && bp_is_group_media_support_enabled() && ! empty( $activity->id ) ) {
															$media_ids = bp_activity_get_meta( $activity->id, 'bp_media_ids', true );

															if ( ! empty( $media_ids ) ) {
																$media_ids       = explode( ',', $media_ids );
																$total_media_ids = count( $media_ids );
																$media_ids       = implode( ',', array_slice( $media_ids, 0, 5 ) );
															}
														}

														$video_ids       = '';
														$total_video_ids = 0;
														if ( bp_is_active( 'media' ) && bp_is_group_video_support_enabled() && ! empty( $activity->id ) ) {
															$video_ids = bp_activity_get_meta( $activity->id, 'bp_video_ids', true );

															if ( ! empty( $video_ids ) ) {
																$video_ids       = explode( ',', $video_ids );
																$total_video_ids = count( $video_ids );
																$video_ids       = implode( ',', array_slice( $video_ids, 0, 5 ) );
															}
														}

														$document_ids       = '';
														$total_document_ids = 0;
														if ( bp_is_active( 'media' ) && bp_is_group_document_support_enabled() && ! empty( $activity->id ) ) {
															$document_ids = bp_activity_get_meta( $activity->id, 'bp_document_ids', true );

															if ( ! empty( $document_ids ) ) {
																$document_ids       = explode( ',', $document_ids );
																$total_document_ids = count( $document_ids );
																$document_ids       = implode( ',', array_slice( $document_ids, 0, 5 ) );
															}
														}

														$gif_data = array();
														if ( bp_is_active( 'media' ) && bp_is_groups_gif_support_enabled() && ! empty( $activity->id ) ) {
															$gif_data = bp_activity_get_meta( $activity->id, '_gif_data', true );
														}

														if (
															! empty( $media_ids ) &&
															bp_has_media(
																array(
																	'include'  => $media_ids,
																	'order_by' => 'menu_order',
																	'sort'     => 'ASC',
																)
															)
														) {
															?>
															<div class="bb-activity-media-wrap" style="padding: 15px 0; width: 250px; height: 200px;">
																<?php
																while ( bp_media() ) {
																	bp_the_media();

																	$media_id      = 'forbidden_' . bp_get_media_id();
																	$attachment_id = 'forbidden_' . bp_get_media_attachment_id();
																	$media_url     = home_url( '/' ) . 'bb-media-preview/' . base64_encode( $attachment_id ) . '/' . base64_encode( $media_id );
																	?>
																	<div class="bb-activity-media-elem" style="width: 250px; vertical-align: top; height: 200px; overflow: hidden;padding:0;">
																		<a href="<?php echo esc_url( $tokens['activity.url'] ); ?>">
																			<img style="border-radius: 4px; min-width: 100%; min-height: 100%; max-width: 100%; object-fit: cover;" src="<?php echo esc_url( $media_url ); ?>" alt="<?php echo esc_attr( bp_get_media_title() ); ?>"/>
																		</a>
																	</div>
																	<?php if ( $total_media_ids > 1 ) : ?>
																		<p style="height: 6px;border-radius: 0px 0px 4px 4px;max-width: 240px;margin: 0;margin-left: 5px;width:100%;background-color: #b5b7bb;padding:0;"></p>
																		<p style="height: 6px;border-radius: 0px 0px 4px 4px;max-width: 222px;margin: 0;margin-left: 14px;width:100%;background-color: #e1e4e8;padding:0;"></p>
																	<?php endif; ?>
																	<?php
																	break;
																}
																?>
															</div>
															<?php
														}

														if (
															! empty( $video_ids ) &&
															bp_has_video(
																array(
																	'include'  => $video_ids,
																	'order_by' => 'menu_order',
																	'sort'     => 'ASC',
																)
															)
														) {
															?>
															<div class="bb-activity-media-wrap" style="padding: 15px 0; width: 250px;">
																<?php
																while ( bp_video() ) {
																	bp_the_video();
																	$poster_thumb = bp_get_video_activity_thumb();
																	if ( empty( $poster_thumb ) ) {
																		$poster_thumb = bp_get_video_popup_thumb();
																	}
																	if ( empty( $poster_thumb ) ) {
																		$poster_thumb = bb_get_video_default_placeholder_image();
																	}
																	?>
																	<div class="bb-activity-media-elem" style="background-image: url('<?php echo esc_url( $poster_thumb ); ?>'); background-size:cover; display: block; width: 250px; vertical-align: top; height: 145px; overflow: hidden; padding: 0; border-radius: 4px;padding:0;">
																		<a href="<?php echo esc_url( $tokens['activity.url'] ); ?>">
																			<img style="display: block; height: 60px;width: 60px; background-color: #fff; border-radius: 50%; margin: 42.5px 0 0 95px" src="<?php echo esc_url( buddypress()->plugin_url ); ?>bp-templates/bp-nouveau/images/video-play.svg" alt="<?php echo esc_attr( bp_get_video_title() ); ?>"/>
																		</a>
																	</div>
																	<?php if ( $total_video_ids > 1 ) : ?>
																		<p style="height: 6px;border-radius: 0px 0px 4px 4px;max-width: 240px;margin: 0;margin-left: 5px;width:100%;background-color: #b5b7bb;padding:0;"></p>
																		<p style="height: 6px;border-radius: 0px 0px 4px 4px;max-width: 222px;margin: 0;margin-left: 14px;width:100%;background-color: #e1e4e8;padding:0;"></p>
																		<?php
																	endif;

																	break;
																}
																?>
															</div>
															<?php
														}

														if (
															! empty( $document_ids ) &&
															bp_has_document(
																array(
																	'include'  => $document_ids,
																	'order_by' => 'menu_order',
																	'sort'     => 'ASC',
																	'per_page' => 5,
																)
															)
														) {
															?>
															<div class="bb-activity-media-wrap" style="padding: 15px 0 15px 0; width: 250px;">
																<?php
																while ( bp_document() ) {
																	bp_the_document();
																	$attachment_id = bp_get_document_attachment_id();
																	$filename      = basename( get_attached_file( $attachment_id ) );
																	$size          = is_file( get_attached_file( $attachment_id ) ) ? bp_document_size_format( filesize( get_attached_file( $attachment_id ) ) ) : 0;
																	$extension     = bp_get_document_extension();
																	?>
																	<div class="bb-activity-media-elem" style="width:100%">
																		<a href="<?php echo esc_url( $tokens['activity.url'] ); ?>" style="font-size:14px; text-decoration:none;">
																			<span style="font-weight:500;"><?php echo esc_html( $filename ); ?></span>
																			<span style="font-size: 13px; margin-left:5px; color: <?php echo esc_attr( $settings['body_text_color'] ); ?>;"><?php echo esc_html( strtolower( $size ) ); ?></span>
																			<span style="font-size: 13px; margin-left:3px; text-transform: uppercase; color: <?php echo esc_attr( $settings['body_text_color'] ); ?>;"><?php echo $extension ? esc_attr( $extension ) : ''; ?></span>
																		</a>
																	</div>
																	<?php
																}
																?>
																<?php if ( $total_document_ids > 5 ) : ?>
																	<a href=""><?php sprintf( __( 'and %d more', 'buddyboss' ), $total_document_ids - 5 ); ?></a>
																<?php endif; ?>
															</div>
															<?php
														}

														if ( ! empty( $gif_data ) ) {
															?>
															<div style="padding: 15px 0;">
																<div>
																	<a href="<?php echo esc_url( $tokens['activity.url'] ); ?>" class="gif-play-button">
																		<?php if ( is_int( $gif_data['still'] ) ) { ?>
																			<img style="max-width: 250px;max-height: 185px;object-fit: cover;border-radius: 4px;" alt="" src="<?php echo esc_url( wp_get_attachment_url( $gif_data['still'] ) ); ?>"/>
																		<?php } else { ?>
																			<img style="max-width: 250px;max-height: 185px;object-fit: cover;border-radius: 4px;" alt="" src="<?php echo esc_url( $gif_data['still'] ); ?>"/>
																		<?php } ?>
																	</a>
																</div>
															</div>
															<?php
														}
														?>
													</td>
												</tr>
												<tr>
													<td height="15px" style="font-size: 15px; line-height: 15px;">&nbsp;</td>
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
				<td height="24px" style="font-size: 24px; line-height: 24px;">&nbsp;</td>
			</tr>

			<tr>
				<td><a href="<?php echo esc_url( $tokens['activity.url'] ); ?>" target="_blank" rel="nofollow" style="font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: 14px; color: <?php echo esc_attr( $settings['highlight_color'] ); ?>; text-decoration: none; display: inline-block; border: 1px solid <?php echo esc_attr( $settings['highlight_color'] ); ?>; border-radius: 100px; min-width: 64px; text-align: center; height: 20px; line-height: 20px; padding: 9px 18px;"><?php esc_html_e( 'View Post', 'buddyboss' ); ?></a></td>
			</tr>
		</table>
		<div class="spacer" style="font-size: 10px; line-height: 10px; height: 10px;">&nbsp;</div>
		<?php
		$output = str_replace( array( "\r", "\n" ), '', ob_get_clean() );

		return $output;
	}

	/**
	 * Generate the output for token commenter.name
	 *
	 * @since BuddyBoss 2.3.50
	 *
	 * @param \BP_Email $bp_email         Core component classes.
	 * @param array     $formatted_tokens Formatted token array.
	 * @param array     $tokens           Token array.
	 *
	 * @return string html for the output.
	 */
	public function token__commenter_name( $bp_email, $formatted_tokens, $tokens ) {

		$user_id        = isset( $tokens['commenter.id'] ) ? $tokens['commenter.id'] : false;
		$commenter_name = '';

		if ( ! empty( $user_id ) ) {
			$commenter_name = bp_core_get_user_displayname( $user_id );
		} elseif ( ! empty( $tokens['commenter.name'] ) ) {
			$commenter_name = $tokens['commenter.name'];
		}

		return $commenter_name;
	}

	/**
	 * Generate the output for token comment_reply.
	 *
	 * @since BuddyBoss 2.3.50
	 *
	 * @param \BP_Email $bp_email         Core component classes.
	 * @param array     $formatted_tokens Formatted token array.
	 * @param array     $tokens           Token array.
	 *
	 * @return string html for the output.
	 */
	public function token__comment_reply( $bp_email, $formatted_tokens, $tokens ) {

		$output        = '';
		$comment_reply = '';

		$settings     = bp_email_get_appearance_settings();
		$comment_id   = isset( $tokens['comment.id'] ) ? $tokens['comment.id'] : false;
		$commenter_id = isset( $tokens['commenter.id'] ) ? $tokens['commenter.id'] : false;
		if ( ! empty( $tokens['comment_reply'] ) ) {
			$comment_reply = $tokens['comment_reply'];
		}

		if ( empty( $comment_id ) ) {
			return $comment_reply;
		}

		$commenter_url        = ! empty( $commenter_id ) ? esc_url( bp_core_get_user_domain( $commenter_id ) ) : '#';
		$commenter_avatar_url = bp_core_fetch_avatar(
			array(
				'item_id' => $commenter_id,
				'width'   => 100,
				'height'  => 100,
				'type'    => 'full',
				'html'    => false,
			)
		);

		$commenter_name = isset( $tokens['commenter.name'] ) ? $tokens['commenter.name'] : '';

		ob_start();
		?>
		<table cellspacing="0" cellpadding="0" border="0" width="100%">
			<tr>
				<td align="center">
					<table cellpadding="0" cellspacing="0" border="0" width="100%">
						<tbody>
						<tr>
							<td valign="middle" width="65px" style="vertical-align: middle;">
								<a style="display: block; width: 47px;" href="<?php echo $commenter_url; ?>" target="_blank" rel="nofollow">
									<img alt="" src="<?php echo esc_url( $commenter_avatar_url ); ?>" width="47" height="47" border="0" style="margin:0; padding:0; border:none; display:block; max-width: 47px; border-radius: 50%;" />
								</a>
							</td>
							<td width="88%" style="vertical-align: middle;">
								<div style="color: <?php echo esc_attr( $settings['body_secondary_text_color'] ); ?>; font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: <?php echo esc_attr( $settings['body_text_size'] . 'px' ); ?>; line-height: <?php echo esc_attr( $settings['body_text_size'] . 'px' ); ?>; letter-spacing: -0.24px;"><?php echo $commenter_name; ?></div>
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
					<table cellspacing="0" cellpadding="0" border="0" width="100%" style="background: <?php echo esc_attr( $settings['quote_bg'] ); ?>; border: 1px solid <?php echo esc_attr( $settings['body_border_color'] ); ?>; border-radius: 4px; border-collapse: separate !important">
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
													<div class="bb-content-body" style="font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: <?php echo esc_attr( $settings['body_text_size'] . 'px' ); ?>; letter-spacing: -0.24px; line-height: <?php echo esc_attr( floor( $settings['body_text_size'] * 1.625 ) . 'px' ); ?>; color: <?php echo esc_attr( $settings['body_secondary_text_color'] ); ?>;">
														<?php
														echo wpautop( $comment_reply );
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
				<td><a href="<?php echo esc_url( $tokens['comment.url'] ); ?>" target="_blank" rel="nofollow" style="font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: 14px; color: <?php echo esc_attr( $settings['highlight_color'] ); ?>; text-decoration: none; display: inline-block; border: 1px solid <?php echo esc_attr( $settings['highlight_color'] ); ?>; border-radius: 100px; min-width: 64px; text-align: center; height: 16px; line-height: 16px; padding: 10px 28px;"><?php esc_html_e( 'View Comment', 'buddyboss' ); ?></a></td>
			</tr>
		</table>
		<div class="spacer" style="font-size: 10px; line-height: 10px; height: 10px;">&nbsp;</div>
		<?php
		$output = str_replace( array( "\r", "\n" ), '', ob_get_clean() );

		return $output;
	}

	/**
	 * Generate the output for token comment.url
	 *
	 * @since BuddyBoss 2.3.50
	 *
	 * @param \BP_Email $bp_email         Core component classes.
	 * @param array     $formatted_tokens Formatted token array.
	 * @param array     $tokens           Token array.
	 *
	 * @return string html for the output.
	 */
	public function token__comment_reply_url( $bp_email, $formatted_tokens, $tokens ) {

		$comment_reply_url = '';
		if ( ! empty( $tokens['comment.url'] ) ) {
			$comment_reply_url = str_replace( array( "\r", "\n" ), '', $tokens['comment.url'] );
		}

		return $comment_reply_url;
	}

	/**
	 * Generate the output for token commenter.url
	 *
	 * @since BuddyBoss 2.3.50
	 *
	 * @param \BP_Email $bp_email         Core component classes.
	 * @param array     $formatted_tokens Formatted token array.
	 * @param array     $tokens           Token array.
	 *
	 * @return string html for the output.
	 */
	public function token__commenter_url( $bp_email, $formatted_tokens, $tokens ) {
		$commenter_id  = isset( $tokens['commenter.id'] ) ? $tokens['commenter.id'] : false;
		$commenter_url = ! empty( $commenter_id ) ? esc_url( bp_core_get_user_domain( $commenter_id ) ) : '#';

		return $commenter_url;
	}

}
