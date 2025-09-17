<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$forums = array(
	array(
		'name'        => 'Public Forum',
		'description' => 'This forums\'s content is visible to the public.',
		'visibility'  => 'publish',
		'status'      => 'open',
	),
	array(
		'name'        => 'Private Forum',
		'description' => 'Only logged in registered members can see this forum\'s content.',
		'visibility'  => 'private',
		'status'      => 'open',
	),
	array(
		'name'        => 'Hidden Forum',
		'description' => 'This forum is only visible to site admins and members added into the forum.',
		'visibility'  => 'hidden',
		'status'      => 'open',
	),
);
