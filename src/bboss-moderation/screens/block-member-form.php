<?php
/**
 * Block Member form
 *
 * @since   BuddyBoss 1.5.6
 * @package BuddyBoss
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

?>

<div id="block-member" class="block-member-popup moderation-popup mfp-hide">
	<div class="modal-mask bb-white bbm-model-wrap bbm-uploader-model-wrap">
			<div class="modal-wrapper">
				<div class="modal-container">
					<header class="bb-model-header">
						<h4><?php esc_html_e( 'Block Member?', 'buddyboss-community-social-network-groups-forums-messaging' ); ?></h4>
						<button title="<?php esc_html_e( 'Close (Esc)', 'buddyboss-community-social-network-groups-forums-messaging' ); ?>" type="button" class="mfp-close">
							<span class="bb-icon-l bb-icon-times"></span>
						</button>
					</header>

					<div class="bb-report-type-wrp">
						<p>
							<?php esc_html_e( 'Please confirm you want to block this member.', 'buddyboss-community-social-network-groups-forums-messaging' ); ?>
						</p>
						<p>
							<?php esc_html_e( 'You will no longer be able to:', 'buddyboss-community-social-network-groups-forums-messaging' ); ?>
						</p>
						<ul>
							<?php if ( bboss_is_active( 'activity' ) ) : ?>
							<li>
								<?php
								esc_html_e( 'See blocked member\'s posts', 'buddyboss-community-social-network-groups-forums-messaging' );
								?>
							</li>
							<?php endif; ?>
							<li>
								<?php
								esc_html_e( 'Mention this member in posts', 'buddyboss-community-social-network-groups-forums-messaging' );
								?>
							</li>
							<?php if ( bboss_is_active( 'groups' ) ) : ?>
							<li>
								<?php
								esc_html_e( 'Invite this member to groups', 'buddyboss-community-social-network-groups-forums-messaging' );
								?>
							</li>
							<?php endif; ?>
							<?php if ( bboss_is_active( 'messages' ) ) : ?>
							<li>
								<?php
								esc_html_e( 'Message this member', 'buddyboss-community-social-network-groups-forums-messaging' );
								?>
							</li>
							<?php endif; ?>
							<?php if ( bboss_is_active( 'friends' ) ) : ?>
							<li>
								<?php
								esc_html_e( 'Add this member as a connection', 'buddyboss-community-social-network-groups-forums-messaging' );
								?>
							</li>
							<?php endif; ?>
						</ul>

						<p>
							<?php if ( bboss_is_active( 'friends' ) ) : ?>
								<strong><?php esc_html_e( 'Please note: ', 'buddyboss-community-social-network-groups-forums-messaging' ); ?></strong>
								<?php esc_html_e( 'This action will also remove this member from your connections and send a report to the site admin.', 'buddyboss-community-social-network-groups-forums-messaging' ); ?>
							<?php endif; ?>

							<?php esc_html_e( 'Please allow a few minutes for this process to complete.', 'buddyboss-community-social-network-groups-forums-messaging' ); ?>
						</p>
						<form id="bb-block-member" action="javascript:void(0);">
							<footer class="bb-model-footer">
								<input type="button" class="bb-cancel-report-content button" value="<?php esc_attr_e( 'Cancel', 'buddyboss-community-social-network-groups-forums-messaging' ); ?>"/>
								<button type="submit" class="report-submit button"><?php esc_attr_e( 'Confirm', 'buddyboss-community-social-network-groups-forums-messaging' ); ?></button>
								<input type="hidden" name="content_id" class="bp-content-id"/>
								<input type="hidden" name="content_type" class="bp-content-type"/>
								<input type="hidden" name="_wpnonce" class="bp-nonce"/>
							</footer>
						</form>
						<?php do_action( 'bp_moderation_block_member_after_form' ); ?>
						<div class="bp-report-form-err"></div>
					</div>

				</div>
			</div>
	</div>

</div>
