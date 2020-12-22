<?php
/**
 * Block Member form
 *
 * @since   BuddyBoss 2.0.0
 * @package BuddyBoss
 */

?>

<div id="block-member" class="block-member-popup moderation-popup mfp-hide">
	<div class="modal-mask bb-white bbm-model-wrap bbm-uploader-model-wrap">
			<div class="modal-wrapper">
				<div class="modal-container">
					<header class="bb-model-header">
						<h4><?php esc_html_e( 'Block Member', 'buddyboss' ); ?></h4>
						<button title="Close (Esc)" type="button" class="mfp-close"></button>
					</header>

					<div class="bb-report-type-wrp">
						<p>
							<?php esc_html_e( 'Are you sure you want to block selected member?', 'buddyboss' ); ?>
							<br/>
							<?php esc_html_e( 'You will no longer be able to:', 'buddyboss' ); ?>
						</p>
						<ul>
							<?php if ( bp_is_active( 'activity' ) ): ?>
							<li>
								<?php
								esc_html_e( 'See blocked member\'s posts', 'buddyboss' );
								?>
							</li>
							<?php endif; ?>
							<li>
								<?php
								esc_html_e( 'Mention blocked member', 'buddyboss' );
								?>
							</li>
							<?php if ( bp_is_active( 'groups' ) ) : ?>
							<li>
								<?php
								esc_html_e( 'Invite blocked member in groups', 'buddyboss' );
								?>
							</li>
							<?php endif; ?>
							<?php if ( bp_is_active( 'messages' ) ): ?>
							<li>
								<?php
								esc_html_e( 'Message blocked member', 'buddyboss' );
								?>
							</li>
							<?php endif; ?>
							<?php if ( bp_is_active( 'friends' ) ): ?>
							<li>
								<?php
								esc_html_e( 'Add blocked member in your connection', 'buddyboss' );
								?>
							</li>
							<?php endif; ?>
						</ul>
						<p>
							<?php if ( bp_is_active( 'friends' ) ): ?>
								<?php esc_html_e( 'Note: If you\'re connected, This action will remove connection with the blocked member.', 'buddyboss' ); ?>
								<br/>
							<?php endif; ?>

							<?php esc_html_e( 'All member specific content will be hidden for you in a few mins.', 'buddyboss' ); ?>
						</p>
						<form id="bb-block-member" action="javascript:void(0);">
							<footer class="bb-model-footer">
								<input type="button" class="bb-cancel-report-content button" value="<?php esc_attr_e( 'Cancel', 'buddyboss' ); ?>"/>
								<button type="submit" class="report-submit button"><?php esc_attr_e( 'Confirm', 'buddyboss' ); ?></button>
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
