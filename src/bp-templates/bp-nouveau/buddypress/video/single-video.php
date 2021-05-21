<?php
/**
 * BuddyBoss - Activity Video
 *
 * @package BuddyBoss\Core
 *
 * @since BuddyBoss 1.7.0
 */

?>
<video id="theatre-video-<?php bp_video_id(); ?>" autoplay class="video-js" controls poster="<?php bp_video_attachment_image(); ?>" data-setup='{"fluid": true,"playbackRates": [0.5, 1, 1.5, 2] }'>
	<source src="<?php bp_video_link(); ?>" type="<?php bp_video_type(); ?>">
	</source>
</video>
