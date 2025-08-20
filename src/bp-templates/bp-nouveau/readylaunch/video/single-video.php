<?php
/**
 * ReadyLaunch - Single Video template.
 *
 * Template for displaying a single video.
 *
 * @package BuddyBoss\Template
 * @subpackage BP_Nouveau\ReadyLaunch
 * @since BuddyBoss 2.9.00
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

?>
<video playsinline id="bb-rl-theatre-video-<?php bp_video_id(); ?>" class="video-js" controls poster="<?php bp_video_popup_thumb(); ?>" data-setup='{"aspectRatio": "16:9", "fluid": true,"playbackRates": [0.5, 1, 1.5, 2], "fullscreenToggle" : false }'>
	<source src="<?php bp_video_link(); ?>" type="<?php bp_video_type(); ?>"></source>
</video>
