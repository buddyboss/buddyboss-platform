<?php
/**
 * The header for ReadyLaunch.
 *
 * @package ReadyLaunch
 */

?>
<!doctype html>
<html <?php language_attributes(); ?>>

<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="profile" href="https://gmpg.org/xfn/11">

	<?php wp_head(); ?>
</head>

<body <?php body_class( 'bb-reaylaunch-template' ); ?>>
<?php wp_body_open(); ?>
<div id="page" class="site app-layout">
	<header id="masthead" class="site-header-1">
		ReadyLaunch HEADER
	</header>
	<main id="primary" class="site-main">
