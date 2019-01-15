<div class="bboss_ajax_search_item bboss_ajax_search_group">
	<a href="<?php echo esc_url(add_query_arg( array( 'no_frame' => '1' ), bp_get_group_permalink() )); ?>">
		<div class="item-avatar">
			<?php bp_group_avatar( 'type=thumb&width=50&height=50' ); ?>
		</div>

		<div class="item">
			<div class="item-title"><?php bp_group_name(); ?></div>
            <?php 
                $content = wp_strip_all_tags(bp_get_group_description());
                $trimmed_content = wp_trim_words( $content, 9, '...' );
            ?>
			<div class="item-desc"><?php echo $trimmed_content ?></div>
		</div>
	</a>
</div>