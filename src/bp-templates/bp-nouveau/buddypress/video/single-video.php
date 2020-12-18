<?php

/**
 * BuddyBoss - Activity Video
 *
 * @since BuddyBoss 1.0.0
 */
?>

<?php global $video_template; ?>
<video id="video-<?php bp_video_id(); ?>" class="video-js" controls poster="<?php bp_video_attachment_image(); ?>" data-setup='{"fluid": true,"playbackRates": [0.5, 1, 1.5, 2] }'>
    <source src="<?php bp_video_link(); ?>" type="<?php bp_video_type(); ?>">
    </source>
</video>