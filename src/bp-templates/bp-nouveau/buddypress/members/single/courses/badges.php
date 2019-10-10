<?php
$user_id = bp_displayed_user_id();
$badges  = bp_learndash_get_users_badges( $user_id );
?>

<div id="bb-learndash-profile" class="bb-certificates-wrapper">
	<?php if ( ! empty( $badges ) ) { ?>
		
		<ul id="badge_list" class="bb-grid">
			<?php foreach ( $badges as $badge ) { ?>
				
                <li class="sm-grid-1-1 md-grid-1-2 lg-grid-1-2">
					<div class="bb-badge-wrap">
						<?php if( !empty($badge->image) ) { ?>
							<a class="bb-badge-img" href="<?php echo $badge->link; ?>" title="<?php echo $badge->title; ?>">
								<img src="<?php echo $badge->image; ?>" alt="<?php echo $badge->title; ?>"/>
							</a>
						<?php } ?>

						<div class="bb-badge-content">
							<h3 class="bb-badge-title"><a href="<?php echo $badge->link; ?>"><?php echo $badge->title; ?></a></h3>
							<div><?php echo wp_trim_words( $badge->content, 10, '&hellip;' ); ?></div>
						</div>
					</div>
                </li>

	        ?>
        </ul>
        
    <?php } } else { ?>

		<aside class="bp-feedback bp-messages info">
			
			<span class="bp-icon" aria-hidden="true"></span>
			<p><?php _e( 'Sorry, no badges were found.', 'buddyboss' ); ?></p>
			
		</aside>

	<?php } ?>
</div>