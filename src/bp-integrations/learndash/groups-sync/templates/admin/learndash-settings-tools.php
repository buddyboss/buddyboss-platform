
<h2><?php _e( 'Group Sync Tool:', 'buddyboss' ); ?></h2>
<div class="sfwd sfwd_options">
    <div id="learndash-buddypress-groups-sync-tools">
    	<div>
			<button
	            class="ld_bp_groups_sync-scan-groups-button button"
	            data-nonce="<?php echo wp_create_nonce('ld_bp_groups_sync-scan-ld-groups'); ?>"
	            data-url="<?php echo admin_url('admin-ajax.php'); ?>"
	            data-slug=""
	        >
	            <?php _e('Scan LearnDash Groups', 'buddyboss'); ?>
	        </button>

	        <div class="spinner" style="float: none;"></div>
	    </div>

        <p><?php _e('Scan for unassociated LearnDash groups. This tool is useful if you already have LearnDash groups on your site and want to link up with new or existing BuddyBoss groups.', 'buddyboss'); ?></p>
	</div>

    <div class="ld_bp_groups_sync-scan-results" style="display: none;"></div>
</div>
