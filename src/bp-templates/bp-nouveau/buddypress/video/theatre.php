<?php
/**
 * BuddyBoss - Video Theatre
 *
 * @since BuddyBoss 1.0.0
 */
?>
<div class="bb-video-model-wrapper bb-internal-model video video-theatre" style="display: none;"  id="buddypress">

    <a data-balloon-pos="left" data-balloon="<?php _e( 'Close', 'buddyboss' ); ?>" class="bb-close-video-theatre bb-close-model" href="#"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14"><path fill="none" stroke="#FFF" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 1L1 13m12 0L1 1" opacity=".7"/></svg></a>

    <div id="bb-video-model-container" class="bb-video-model-container">
        <div class="bb-video-model-inner">
            <div class="bb-video-section">
                <a class="theater-command bb-prev-video" href="#previous">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="30"><path fill="none" stroke="#FFF" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 29L1 15 15 1" opacity=".7"/></svg>
                </a>

                <a class="theater-command bb-next-video" href="#next">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="30"><path fill="none" stroke="#FFF" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M1 1l14 14L1 29" opacity=".7"/></svg>
                </a>

                <figure class="">
                    <img src="" alt="" />
                </figure>

	            <div class="bb-dropdown-wrap bb-video-only-privacy">
		            <div class="bb-video-privacy-wrap" style="display: none;">
			            <span class="bp-tooltip privacy-wrap" data-bp-tooltip-pos="left" data-bp-tooltip=""><span class="privacy selected"></span></span>
			            <ul class="video-privacy">
				            <?php foreach( bp_video_get_visibility_levels() as $item_key => $privacy_item ) {
					            ?><li data-value="<?php echo esc_attr( $item_key ); ?>" class="<?php echo esc_attr( $item_key ); ?>"><?php echo esc_attr( $privacy_item ); ?></li><?php
				            } ?>
			            </ul>
		            </div>
	            </div>

            </div>
            <div class="bb-video-info-section video">
                <ul class="activity-list item-list bp-list"><span><i class="bb-icon-spin5 animate-spin"></i></span></ul>
            </div>
        </div>
    </div>

</div>
