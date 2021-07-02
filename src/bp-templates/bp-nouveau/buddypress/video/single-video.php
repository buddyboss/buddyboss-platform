<?php
/**
 * BuddyBoss - Activity Video
 *
 * @package BuddyBoss\Core
 *
 * @since BuddyBoss 1.7.0
 */

?>
<video playsinline id="theatre-video-<?php bp_video_id(); ?>" class="video-js" controls poster="<?php bp_video_popup_thumb(); ?>" data-setup='{"aspectRatio": "16:9", "fluid": true,"playbackRates": [0.5, 1, 1.5, 2] }'>
	<source src="<?php bp_video_link(); ?>" type="<?php bp_video_type(); ?>"></source>
</video>
