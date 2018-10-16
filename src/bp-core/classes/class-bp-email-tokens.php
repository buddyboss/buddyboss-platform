<?php
/**
 * Core component classes.
 *
 * @package BuddyBoss
 * @subpackage Core
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Represents an email tokens that will be sent in emails.
 *
 * @since BuddyBoss 3.1.1
 */
class BP_Email_Tokens {

	/**
	 * message sender id
	 *
	 * @since BuddyBoss 3.1.1
	 */
	protected $_message_sender_id = false;

	/**
	 * Constructor.
	 *
	 * @since BuddyBoss 3.1.1
	 */
	public function __construct() {
		// set new email tokens added in BuddyBoss 3.1.1
		add_filter( 'bp_email_set_tokens', array( $this, 'set_tokens' ), 10, 3 );

		//tokens for email after a new message is received, does not contain usable info about sender user
		//we need to acquire this info before we process tokens for that email
		//priority 9 is importent
		add_action( 'messages_message_sent', array( $this, 'messages_message_sent' ), 9 );
	}

	/**
	 * Set email tokens
	 *
	 * @param array $formatted_tokens
	 * @param array $tokens
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
						$token_output                                          = call_user_func( $token_details['function'], $bp_email, $formatted_tokens, $tokens );
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
	 * @since BuddyBoss 3.1.1
	 *
	 * @return array
	 */
	public function get_tokens() {

		$tokens = array(
			'group.small_card' => array(
				'function'    => array( $this, 'token__group_card_small' ),
				'description' => __( 'Display the group card, with minimum details about the group.', 'buddyboss' ),
			),
			'group.card'       => array(
				'function'    => array( $this, 'token__group_card' ),
				'description' => __( 'Display the group card, with more details like group cover photo etc.', 'buddyboss' ),
			),
			'group.url'        => array(
				'function'    => array( $this, 'token__group_url' ),
				'description' => __( 'Outputs the link to the group.', 'buddyboss' ),
			),
			'message'          => array(
				'function'    => array( $this, 'token__message' ),
				'description' => __( 'Display the sent message, along with sender\'s picture and name.', 'buddyboss' ),
			),
			'sender.url'       => array(
				'function'    => array( $this, 'token__sender_url' ),
				'description' => __( 'Outputs the link to the profile of member who has sent the message. Only works in email that is sent to a member when someone sends him/her a message.', 'buddyboss' ),
			),
			'member.card'      => array(
				'function'    => array( $this, 'token__member_card_small' ),
				'description' => __( 'Display the member card, with minimum details about the member.', 'buddyboss' ),
			),
			'status_update'    => array(
				'function'    => array( $this, 'token__status_update' ),
				'description' => __( 'Display the status update, along with member\'s picture and name.', 'buddyboss' ),
			),
			'activity_reply'   => array(
				'function'    => array( $this, 'token__activity_reply' ),
				'description' => __( 'Display the reply to  update, along with member\'s picture and name.', 'buddyboss' ),
			),
			'poster.url'       => array(
				'function'    => array( $this, 'token__poster_url' ),
				'description' => __( 'Outputs the link to the profile of member who has posted the update.', 'buddyboss' ),
			),
		);

		return $tokens;
	}

	/**
	 * Generate the output for toke group.small_card
	 *
	 * @since BuddyBoss 3.1.1
	 *
	 * @param \BP_Email $bp_email
	 * @param array $formatted_tokens
	 * @param array $tokens
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

		ob_start();
		?>
        <table cellspacing="0" cellpadding="0" border="0" width="100%"
               style="background: #ffffff; border: 1px solid #E7E9EC; border-radius: 4px; border-collapse: separate !important">
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
                                        <td width="20%">
                                            <a href="<?php echo bp_get_group_permalink( $group ); ?>"
                                               style="background: #FFFFFF; border: 1px solid #E7E9EC; display: block; border-radius: 3px; width: 100px;">
                                                <img alt="" src="<?php echo bp_core_fetch_avatar( array(
													'item_id'    => $group->id,
													'avatar_dir' => 'group-avatars',
													'object'     => 'group',
													'width'      => 200,
													'height'     => 200,
													'html'       => false
												) ); ?>" width="100" height="100"
                                                     style="margin:0; padding:0; box-sizing: border-box; border-radius: 3px; border: 3px solid #FFFFFF; display:block;"
                                                     border="0"/>
                                            </a>
                                        </td>
                                        <td width="4%">&nbsp;</td>
                                        <td width="76%">
                                            <table cellpadding="0" cellspacing="0" border="0" width="100%">
                                                <tbody>
                                                <tr>
                                                    <td>
                                                        <div style="font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: <?php echo esc_attr( floor( $settings['body_text_size'] * 1.625 ) . 'px' ) ?>; color: #122B46; line-height: <?php echo esc_attr( floor( $settings['body_text_size'] * 1.625 ) . 'px' ) ?>;"><?php echo $group->name; ?></div>
                                                        <div class="spacer"
                                                             style="font-size: 7px; line-height: 7px; height: 7px;">
                                                            &nbsp;
                                                        </div>
                                                        <p style="opacity: 0.7; font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: <?php echo esc_attr( floor( $settings['body_text_size'] * 0.8125 ) . 'px' ) ?>; color: <?php echo esc_attr( $settings['body_text_color'] ); ?>; margin: 0;"><?php echo ucfirst( $group->status ) . " " . __( 'Group', 'buddyboss' ); ?></p>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td height="16px" style="font-size: 16px; line-height: 16px;">
                                                        &nbsp;
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td>
                                                        <table cellpadding="0" cellspacing="0" border="0" width="100%">
                                                            <tbody>
                                                            <tr>
                                                                <td>
                                                                    <!-- LEFT COLUMN -->
                                                                    <table cellpadding="0" cellspacing="0" border="0"
                                                                           width="47%" style="width: 47%;" align="left"
                                                                           class="responsive-table">
                                                                        <tbody>
                                                                        <tr>
                                                                            <td height="34px"
                                                                                style="vertical-align: middle;">
                                                                                <div style="font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;font-size: <?php echo esc_attr( floor( $settings['body_text_size'] * 0.8125 ) . 'px' ) ?>; color: #939597;">
                                                                                    <span style="color: #4D5C6D;"><?php echo bp_get_group_total_members( $group ); ?></span> <?php _e( 'members', 'buddyboss' ); ?>
                                                                                </div>
                                                                            </td>
                                                                        </tr>
                                                                        </tbody>
                                                                    </table>
                                                                    <!-- RIGHT COLUMN -->
                                                                    <table cellpadding="0" cellspacing="0" border="0"
                                                                           width="47%" style="width: 47%;" align="right"
                                                                           class="responsive-table">
                                                                        <tbody>
                                                                        <tr>
                                                                            <td height="34px" align="right"
                                                                                style="vertical-align: middle;"
                                                                                class="mobile-text-left">
																				<?php if ( isset( $tokens['invites.url'] ) ): ?>
                                                                                    <a href="<?php echo $tokens['invites.url']; ?>"
                                                                                       target="_blank" rel="nofollow"
                                                                                       style="font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;font-size: <?php echo esc_attr( floor( $settings['body_text_size'] * 0.875 ) . 'px' ) ?>;text-decoration: none;display: block;border: 1px solid <?php echo $settings['highlight_color']; ?>;border-radius: 100px;text-align: center; height: 32px;line-height: 32px;background: <?php echo $settings['highlight_color']; ?>;color: #fff;width: 125px;"><?php _e( 'Accept Invitation', 'buddyboss' ); ?></a>
																				<?php else: ?>
                                                                                    <a href="<?php echo bp_get_group_permalink( $group ); ?>"
                                                                                       target="_blank" rel="nofollow"
                                                                                       style="font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;font-size: <?php echo esc_attr( floor( $settings['body_text_size'] * 0.875 ) . 'px' ) ?>;text-decoration: none;display: block;border: 1px solid <?php echo $settings['highlight_color']; ?>;border-radius: 100px;text-align: center; height: 32px;line-height: 32px;background: <?php echo $settings['highlight_color']; ?>;color: #fff;width: 125px;"><?php _e( 'Visit Group', 'buddyboss' ); ?></a>
																				<?php endif; ?>
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
        <br/>
		<?php
		$output = str_replace( array( "\r", "\n" ), '', ob_get_clean() );

		return $output;
	}

	/**
	 * Generate the output for toke group.card
	 *
	 * @since BuddyBoss 3.1.1
	 *
	 * @param \BP_Email $bp_email
	 * @param array $formatted_tokens
	 * @param array $tokens
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
					$cover_image = bp_attachments_get_attachment( 'url', array(
						'object_dir' => 'groups',
						'item_id'    => $group_id,
					) );
					echo "<tr><td colspan='100%'><img src='{$cover_image}'></td></tr>";
				}
				?>

                <tr>
                    <td>
                        <a href="<?php echo bp_get_group_permalink( $group ); ?>"
                           style="background: #FFFFFF; border: 1px solid #E7E9EC; display: block; border-radius: 3px; width: 100px;">
                            <img alt="" src="<?php echo bp_core_fetch_avatar( array(
								'item_id'    => $group->id,
								'avatar_dir' => 'group-avatars',
								'object'     => 'group',
								'width'      => 200,
								'height'     => 200,
								'html'       => false
							) ); ?>" width="100" height="100"
                                 style="margin:0; padding:0; box-sizing: border-box; border-radius: 3px; border:3px solid #FFFFFF; display:block;"
                                 border="0"/>
                        </a>
                    </td>
                    <td>
                        <h3><?php echo $group->name; ?></h3>
                        <div class="spacer" style="font-size: 7px; line-height: 7px; height: 7px;">&nbsp;</div>
						<?php echo ucfirst( $group->status ) . " " . __( 'Group', 'buddyboss' ); ?><br>
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
											$wpdb->prepare( "component = %s", buddypress()->groups->id ),
										);

										$sql['where'] = 'WHERE ' . implode( ' AND ', $sql['where'] );

										$sql['limit'] = 'LIMIT 4';

										$group_user_ids = $wpdb->get_results( "{$sql[ 'select' ]} {$sql[ 'where' ]} {$sql[ 'groupby' ]} {$sql[ 'orderby' ]} {$sql[ 'order' ]} {$sql[ 'limit' ]}" );

										$group_user_ids = wp_list_pluck( $group_user_ids, 'user_id' );

										$output = "<span class='bs-group-members'>";
										foreach ( $group_user_ids as $user_id ) {
											$avatar = bp_core_fetch_avatar( array(
												'item_id'    => $user_id,
												'avatar_dir' => 'avatars',
												'object'     => 'user',
												'type'       => 'thumb',
												'html'       => false
											) );

											if ( ! empty( $avatar ) ) {
												$output .= sprintf( "<img src='%s' alt='%s'>", $avatar, bp_core_get_user_displayname( $user_id ) );
											}
										}
										$output .= "</span>";

										$output .= "<span class='members'>" . groups_get_total_member_count( $group->id ) . " " . __( 'Members', 'buddyboss' ) . "</span>";
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
            <br/>
        </div>

		<?php
		$output = str_replace( array( "\r", "\n" ), '', ob_get_clean() );

		return $output;
	}

	/**
	 * Generate the output for tokem status_update
	 *
	 * @since BuddyBoss 3.1.1
	 *
	 * @param \BP_Email $bp_email
	 * @param array $formatted_tokens
	 * @param array $tokens
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
                            <td valign="middle" width="10%" style="vertical-align: middle;">
                                <a href="<?php echo esc_attr( bp_core_get_user_domain( $activity->user_id ) ); ?>"
                                   target="_blank" rel="nofollow">
									<?php
									$avatar_url = bp_core_fetch_avatar( array(
										'item_id' => $activity->user_id,
										'width'   => 47,
										'height'  => 47,
										'html'    => false,
									) );
									?>
                                    <img src="<?php echo esc_attr( $avatar_url ); ?>" width="47" height="47"
                                         style="margin:0; padding:0; border:none; display:block; max-width: 47px; border-radius: 50%;"
                                         border="0">
                                </a>
                            </td>
                            <td width="90%" style="vertical-align: middle;">
                                <div style="border-left: 10px solid #fff; font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: <?php echo esc_attr( $settings['body_text_size'] . 'px' ); ?>; line-height: <?php echo esc_attr( $settings['body_text_size'] . 'px' ); ?>; letter-spacing: -0.24px;"><?php echo bp_core_get_user_displayname( $activity->user_id ); ?></div>
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
                           style="background: #F7FAFE; border: 1px solid #E7E9EC; border-radius: 4px; border-collapse: separate !important">
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
                                            <div style="font-size: <?php echo esc_attr( $settings['body_text_size'] . 'px' ); ?>; letter-spacing: -0.24px; line-height: <?php echo esc_attr( floor( $settings['body_text_size'] * 1.625 ) . 'px' ) ?>;">
												<?php echo apply_filters_ref_array( 'bp_get_activity_content_body', array(
													$activity->content,
													&$activity
												) ); ?>
                                            </div>
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
                    <a href="<?php echo esc_attr( $tokens['mentioned.url'] ); ?>" target="_blank" rel="nofollow"
                       style="font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: 14px; color: <?php echo $settings['highlight_color']; ?>; text-decoration: none; display: block; border: 1px solid <?php echo $settings['highlight_color']; ?>; border-radius: 100px; width: 84px; text-align: center; height: 32px; line-height: 32px;"><?php _e( 'Reply', 'buddyboss' ); ?></a>
                </td>
            </tr>
        </table>
        <br/>
		<?php
		$output = str_replace( array( "\r", "\n" ), '', ob_get_clean() );

		return $output;
	}

	/**
	 * Generate the output for tokem activity_reply
	 *
	 * @since BuddyBoss 3.1.1
	 *
	 * @param \BP_Email $bp_email
	 * @param array $formatted_tokens
	 * @param array $tokens
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
                <td>
                    <div style="margin: 0 0 20px; font-size: <?php echo esc_attr( floor( $settings['body_text_size'] * 0.875 ) . 'px' ) ?>; line-height: <?php echo esc_attr( floor( $settings['body_text_size'] * 1.375 ) . 'px' ) ?>;"><?php
						$content   = apply_filters_ref_array( 'bp_get_activity_content_body', array(
							$activity_original->content,
							&$activity_original
						) );
						$limit     = 200;
						$t_content = substr( $content, 0, $limit );
						if ( strlen( $content ) > $limit ) {
							$t_content .= " ...";
						}
						echo $t_content;
						//echo force_balance_tags( $t_content );
						?></div>
                </td>
            </tr>

            <tr>
                <td height="4px" style="font-size: 4px; line-height: 4px;">&nbsp;</td>
            </tr>

            <tr>
                <td>
                    <table cellspacing="0" cellpadding="0" border="0" width="100%"
                           style="background: #F7FAFE; border: 1px solid #E7E9EC; border-radius: 4px; border-collapse: separate !important">
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
                                            <div class="bb-content-body"
                                                 style="font-size: <?php echo esc_attr( $settings['body_text_size'] . 'px' ); ?>; letter-spacing: -0.24px; line-height: <?php echo esc_attr( floor( $settings['body_text_size'] * 1.625 ) . 'px' ) ?>;">
												<?php echo apply_filters_ref_array( 'bp_get_activity_content_body', array(
													$activity_comment->content,
													&$activity_comment
												) ); ?>
                                            </div>
                                        </td>
                                    </tr>
                                    </tbody>
                                </table>
                            </td>
                        </tr>
                        <!--<tr><td height="25px" style="font-size: 25px; line-height: 25px;">&nbsp;</td></tr>-->
                        </tbody>
                    </table>
                </td>
            </tr>

            <tr>
                <td height="24px" style="font-size: 24px; line-height: 24px;">&nbsp;</td>
            </tr>

            <tr>
                <td><a href="<?php echo esc_attr( $tokens['thread.url'] ); ?>" target="_blank" rel="nofollow"
                       style="font-size: 14px; color: <?php echo $settings['highlight_color']; ?>; text-decoration: none; display: block; border: 1px solid <?php echo $settings['highlight_color']; ?>; border-radius: 100px; width: 84px; text-align: center; height: 32px; line-height: 32px;"><?php _e( 'Reply', 'buddyboss' ); ?></a>
                </td>
            </tr>
        </table>
        <br/>
		<?php
		$output = str_replace( array( "\r", "\n" ), '', ob_get_clean() );

		return $output;
	}

	/**
	 * set message sender id
     *
     * @since BuddyBoss 3.1.1
     *
	 * @param \BP_Messages_Message $message
	 */
	public function messages_message_sent( $message ) {
		$this->_message_sender_id = $message->sender_id;
	}

	/**
	 * Generate the output for token message
	 *
	 * @since BuddyBoss 3.1.1
	 *
	 * @param \BP_Email $bp_email
	 * @param array $formatted_tokens
	 * @param array $tokens
	 *
	 * @return string html for the output
	 */
	public function token__message( $bp_email, $formatted_tokens, $tokens ) {
		$output = '';

		if ( 'messages-unread' != $bp_email->get( 'type' ) )
		    return $output;

		$settings = bp_email_get_appearance_settings();
		ob_start();
		?>
        <table cellspacing="0" cellpadding="0" border="0" width="100%">
			<?php if ( $this->_message_sender_id ): ?>
                <tr>
                    <td>
                        <table cellpadding="0" cellspacing="0" border="0" width="100%" style="width: 100%">
                            <tbody>
                            <tr>
                                <td valign="middle" width="10%" style="vertical-align: middle;">
                                    <a href="<?php echo esc_attr( bp_core_get_user_domain( $this->_message_sender_id ) ); ?>"
                                       target="_blank" rel="nofollow">
										<?php $avatar_url = bp_core_fetch_avatar( array(
											'item_id' => $this->_message_sender_id,
											'width'   => 100,
											'height'  => 100,
											'html'    => false,
										) ); ?>
                                        <img src="<?php echo esc_attr( $avatar_url ); ?>" width="47" height="47"
                                             style="margin:0; padding:0; border:none; display:block; max-width: 47px; border-radius: 50%;"
                                             border="0">
                                    </a>
                                </td>
                                <td width="90%" style="vertical-align: middle;">
                                    <div style="border-left: 10px solid #fff; font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: <?php echo esc_attr( $settings['body_text_size'] . 'px' ); ?>; line-height: <?php echo esc_attr( $settings['body_text_size'] . 'px' ); ?>; letter-spacing: -0.24px;">
                                        <a href="<?php echo esc_attr( bp_core_get_user_domain( $this->_message_sender_id ) ); ?>"
                                           target="_blank" rel="nofollow"
                                           style="text-decoration: none; color: #122B46;">
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
                           style="background: #F7FAFE; border: 1px solid #E7E9EC; border-radius: 4px; border-collapse: separate !important">
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
                                            <div style="font-size: <?php echo esc_attr( $settings['body_text_size'] . 'px' ); ?>; letter-spacing: -0.24px; line-height: <?php echo esc_attr( floor( $settings['body_text_size'] * 1.625 ) . 'px' ) ?>;"><?php
												echo nl2br( $tokens['usermessage'] );
												?></div>
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
                       style="font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: 14px; color: <?php echo $settings['highlight_color']; ?>; text-decoration: none; display: block; border: 1px solid <?php echo $settings['highlight_color']; ?>; border-radius: 100px; width: 84px; text-align: center; height: 32px; line-height: 32px;"><?php _e( 'Reply', 'buddyboss' ); ?></a>
                </td>
            </tr>
        </table>
        <br/>
		<?php
		$output = str_replace( array( "\r", "\n" ), '', ob_get_clean() );

		return $output;
	}

	/**
	 * Generate the output for toke member.card
	 *
	 * @since BuddyBoss 3.1.1
	 *
	 * @param \BP_Email $bp_email
	 * @param array $formatted_tokens
	 * @param array $tokens
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

		//maybe search for some other token

		if ( empty( $member_id ) ) {
			return $output;
		}

		ob_start();
		?>

        <table class="member-details" cellspacing="0" cellpadding="0" border="0" width="100%"
               style="background: #ffffff; border: 1px solid #E7E9EC; border-radius: 4px; border-collapse: separate !important">
            <tr>
                <td align="center">
                    <table cellpadding="0" cellspacing="0" border="0" width="100%" style="width: 100%;">
                        <tr>
                            <td>
                                <table cellpadding="0" cellspacing="0" border="0">
                                    <tr>
                                        <td width="20%">
                                            <a href="<?php echo bp_core_get_user_domain( $member_id ); ?>"
                                               style="background: #FFFFFF; display: block; border-radius: 3px; width: 140px;">
                                                <img alt="" src="<?php echo bp_core_fetch_avatar( array(
													'item_id' => $member_id,
													'width'   => 280,
													'height'  => 280,
													'type'    => 'full',
													'html'    => false
												) ); ?>" width="140" height="140"
                                                     style="margin:0; padding:0; border:none; display:block;"
                                                     border="0"/>
                                            </a>
                                        </td>
                                        <td width="4%">&nbsp;</td>
                                        <td width="72%">
                                            <table cellpadding="0" cellspacing="0" border="0" width="100%"
                                                   style="width: 100%;">
                                                <tr>
                                                    <td height="16px" style="font-size: 16px; line-height: 16px;">
                                                        &nbsp;
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td>
                                                        <div style="font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: <?php echo esc_attr( floor( $settings['body_text_size'] * 1.25 ) . 'px' ) ?>; color: #122B46; line-height: <?php echo esc_attr( floor( $settings['body_text_size'] * 1.75 ) . 'px' ) ?>;"><?php echo bp_core_get_user_displayname( $member_id ); ?></div>
                                                        <div class="spacer"
                                                             style="font-size: 6px; line-height: 6px; height: 6px;">
                                                            &nbsp;
                                                        </div>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td height="20px" style="font-size: 20px; line-height: 20px;">
                                                        &nbsp;
                                                    </td>
                                                </tr>
                                            </table>
                                        </td>
                                        <td width="4%">&nbsp;</td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
        <br/>
		<?php
		$output = str_replace( array( "\r", "\n" ), '', ob_get_clean() );

		return $output;
	}

	/**
	 * Generate the output for toke boss.poster.url
	 *
	 * @since BuddyBoss 3.1.1
	 *
	 * @param \BP_Email $bp_email
	 * @param array $formatted_tokens
	 * @param array $tokens
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
			return "";
		}

		return bp_core_get_user_domain( $user_id );
	}

	/**
	 * Generate the output for toke boss.sender.url
	 *
	 * @since BuddyBoss 3.1.1
	 *
	 * @param \BP_Email $bp_email
	 * @param array $formatted_tokens
	 * @param array $tokens
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
	 * Generate the output for toke boss.group.url
	 *
	 * @since BuddyBoss 3.1.1
	 *
	 * @param \BP_Email $bp_email
	 * @param array $formatted_tokens
	 * @param array $tokens
	 *
	 * @return string html for the output
	 */
	public function token__group_url( $bp_email, $formatted_tokens, $tokens ) {
		$group_id = false;

		if ( ! bp_is_active( 'groups' ) ) {
			return '';
		}

		$email_type = $bp_email->get( 'type' );
		switch ( $email_type ) {
			case 'groups-at-message':
				$group_id = bp_get_current_group_id();
				break;
		}

		if ( empty( $group_id ) ) {
			return '';
		}

		$group = groups_get_group( $group_id );
		if ( empty( $group ) ) {
			return '';
		}

		return bp_get_group_permalink( $group );
	}
}
