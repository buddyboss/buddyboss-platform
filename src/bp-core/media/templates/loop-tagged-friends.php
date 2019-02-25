<?php 
//this filter is important!
$args = apply_filters( 'bboss_media_tagged_friends', array(
	'user_id'			=> bp_loggedin_user_id(),
	'type'				=> 'alphabetical',
	'per_page'			=> 0,
	'populate_extras'	=> false,
) );
?>

<?php if( bp_has_members( $args ) ): ?>
	<ul id="friend-list" class="item-list">
	
	<?php while ( bp_members() ) : ?>
		<?php bp_the_member(); ?>
		<li id="uid-<?php bp_member_user_id(); ?>">
			<?php bp_member_avatar();?>

			<h4><?php echo bp_core_get_userlink( bp_get_member_user_id() ); ?></h4>
			<span class="activity"><?php bp_member_last_active(); ?></span>

			<div class="action">
				<a class="button remove" href="#" data-userid="<?php bp_member_user_id(); ?>"><?php _e( 'Remove Tag', 'buddyboss-media' ); ?></a>
			</div>
		</li>
	<?php endwhile; ?>
	
	</ul>
<?php endif;?>