<?php
/**
 * Block Member form
 *
 * @since   BuddyBoss 2.0.0
 * @package BuddyBoss
 *
 */

?>

<div id="block-member" class="block-member-popup bb-modal mfp-hide">
    <h2>
		<?php
		esc_html_e( 'Block Member', 'buddyboss' );
		?>
        <button title="Close (Esc)" type="button" class="mfp-close"></button>
    </h2>
	<?php
	?>
    <div class="bb-report-type-wrp">
		<span>
			<?php esc_html_e( 'You will no longer be able to:', 'buddyboss' );  ?>
    	</span>
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
		<span>
        	<?php esc_html_e( 'If you\'re friends, This action will also unfriend blocked members.', 'buddyboss' ); ?>
    	</span>
        <form id="bb-block-member" action="javascript:void(0);">
            <div class="form-item">
                <input type="button" class="bb-cancel-report-content"
                       value="<?php esc_attr_e( 'Cancel', 'buddyboss' ); ?>"/>
                <input type="submit" value="<?php esc_attr_e( 'Confirm', 'buddyboss' ); ?>" class="report-submit"/>
                <input type="hidden" name="content_id" class="bp-content-id"/>
                <input type="hidden" name="content_type" class="bp-content-type"/>
                <input type="hidden" name="_wpnonce" class="bp-nonce"/>
            </div>
        </form>
	    <?php do_action( 'bp_moderation_block_member_after_form' ); ?>
        <div class="bp-report-form-err"></div>
    </div>
</div>
