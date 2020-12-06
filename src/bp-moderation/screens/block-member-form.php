<?php
/**
 * Block Member form
 *
 * @since   BuddyBoss 2.0.0
 * @package BuddyBoss
 *
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
							<?php esc_html_e( 'You will no longer be able to:', 'buddyboss' );  ?>
						</p>
						<ul>
							<li>
								<?php
								esc_html_e( 'See blocked member\'s posts', 'buddyboss' );
								?>
							</li>
							<li>
								<?php
								esc_html_e( 'Tag blocked member', 'buddyboss' );
								?>
							</li>
							<li>
								<?php
								esc_html_e( 'Invite blocked member in event and groups', 'buddyboss' );
								?>
							</li>
							<li>
								<?php
								esc_html_e( 'Message blocked member', 'buddyboss' );
								?>
							</li>
							<li>
								<?php
								esc_html_e( 'Add blocked member as a friend', 'buddyboss' );
								?>
							</li>
						</ul>
						<p>
							<?php esc_html_e( 'If you\'re friends, This action will also unfriend blocked members.', 'buddyboss' ); ?>
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
