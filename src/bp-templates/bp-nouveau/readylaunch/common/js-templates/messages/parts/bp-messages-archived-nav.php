<?php
/**
 * Readylaunch - Messages nav template.
 *
 * @since   BuddyBoss 2.9.00
 * @version 1.0.0
 */
?>

<script type="text/html" id="tmpl-bp-messages-archived-nav">
	<nav class="bp-navs bp-subnavs no-ajax user-subnav bb-subnav-plain" id="subnav" role="navigation" aria-label="Sub Menu">
		<ul class="subnav">
			<li id="back-to-thread-li" class="bp-personal-sub-tab last">
				<a href="#" id="back-to-thread" aria-label="<?php echo esc_html__( 'Back', 'buddyboss' ); ?>">
					<span class="bb-icon-f bb-icon-arrow-left"></span>
				</a>
				<?php echo esc_html__( 'Archived', 'buddyboss' ); ?>
			</li>
		</ul>
	</nav>
</script>
