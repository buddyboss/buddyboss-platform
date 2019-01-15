<div class="bboss_ajax_search_item bboss_ajax_search_item_post">
	<a href="<?php echo esc_url(add_query_arg( array( 'no_frame' => '1' ), get_permalink() ));?>">
		<div class="item">
			<div class="item-title"><?php the_title();?></div>
			<?php  
                $content = wp_strip_all_tags( get_the_content() );
				preg_match_all("^\[(.*?)\]^", $content, $matches, PREG_PATTERN_ORDER);  //strip all shortcodes in the ajax search content
				$content = str_replace($matches[0], '', $content);
                $trimmed_content = wp_trim_words( $content, 20, '...' ); 
            ?>
			<div class="item-desc"><?php echo $trimmed_content; ?></div>
       
		</div>
	</a>
</div>