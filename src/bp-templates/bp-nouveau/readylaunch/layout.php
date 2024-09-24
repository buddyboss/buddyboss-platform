<?php
/**
 * The layout for templates
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
    <header id="masthead" class="site-header">
		HEADER
    </header><!-- #masthead -->

    <main id="primary" class="site-main">
		<?php
		if ( have_posts() ) :
			/* Start the Loop */
			while ( have_posts() ) :
				the_post();

				the_content();
			endwhile;
		endif;
		?>
    </main>

	  Footer
    <?php wp_footer(); ?>
</body>

</html>
