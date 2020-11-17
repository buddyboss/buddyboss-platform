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
		<?php do_action( 'bp_moderation_block_member_before_form' ); ?>
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
