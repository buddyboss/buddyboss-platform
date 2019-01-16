<?php if (count($subMenus) < 2) return; ?>

<nav class="bp-navs bp-subnavs no-ajax group-subnav" id="subnav" role="navigation" aria-label="<?php esc_attr_e('Group courses menu', 'buddyboss'); ?>">
	<ul class="subnav">
		<?php foreach ($subMenus as $menu): ?>
			<li class="<?php echo $currentMenu == $menu['slug']? 'current selected' : ''; ?>">
				<a href="<?php echo $menu['url']; ?>"><?php echo $menu['name']; ?></a>
			</li>
		<?php endforeach; ?>
	</ul>
</nav>
