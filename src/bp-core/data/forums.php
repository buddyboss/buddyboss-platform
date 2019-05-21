<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$forums = array(
	array(
		'name'        => 'bbPress Forum Standard',
		'description' => 'Foodie is a person who has an ardent or refined interest in food and who eats food not out of hunger but due to their interest or hobby. The terms "gastronome" and "gourmet" define the same thing, i.e. a person who enjoys food for pleasure.',
		'visibility'  => 'publish',
		'status'      => 'open',
	),
	array(
		'name'        => 'bbPress Forum Subscription',
		'description' => 'In nature, nothing is perfect and everything is perfect. Trees can be contorted, bent in weird ways, and they\'re still beautiful.',
		'visibility'  => 'private',
		'status'      => 'closed',
	),
	array(
		'name'        => 'bbPress Forum Pending',
		'description' => 'Every man\'s work, whether it be literature, or music or pictures or architecture or anything else, is always a portrait of himself.',
		'visibility'  => 'hidden',
		'status'      => 'closed',
	),
	array(
		'name'        => 'bbPress Forum Favorite',
		'description' => 'Map out your future – but do it in pencil. The road ahead is as long as you make it. Make it worth the trip.',
		'visibility'  => 'publish',
		'status'      => 'open',
	),
	array(
		'name'        => 'bbPress Forum Super Sticky',
		'description' => 'I really love to ride my motorcycle. When I want to just get away and be by myself and clear my head, that\'s what I do.',
		'visibility'  => 'publish',
		'status'      => 'open',
	),
);
