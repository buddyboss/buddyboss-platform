<?php
/**
 * Filters related to the Moderation component.
 *
 * @package BuddyBoss\Moderation
 * @since BuddyBoss 1.5.4
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

new BP_Moderation_Activity();
new BP_Moderation_Groups();
new BP_Moderation_Members();
new BP_Moderation_Forums();
new BP_Moderation_Forum_Topics();
new BP_Moderation_Forum_Replies();
