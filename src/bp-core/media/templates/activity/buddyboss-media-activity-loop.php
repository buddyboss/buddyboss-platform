<?php do_action( 'bp_before_activity_loop' ); ?>
<script type="text/javascript">

		var jq = $ = jQuery.noConflict();

		(function() {
		var buddyboss_global_photos;

		buddyboss_global_photos = {

			init: function () {
				jq( document ).ajaxComplete( buddyboss_global_photos.ajax_complete_handler );
			},

			/**
			 * Reload masonry after ajax complete
			 * @param event
			 * @param request
			 * @param settings
			 */
			ajax_complete_handler: function (event, request, settings) {
				var action = bbmedia_getQueryVariable(settings.data, 'action');
				 if ('post_update' == action) {
					window.location.reload()
				}
			}
		};

		jq( document).ready( function() { buddyboss_global_photos.init(); });
	})(jq);
</script>

<style type="text/css">


	.photo-grid {
		margin: 0 auto;
	}
	div.photo-item-wrapper {
		margin-bottom: 10px;
		margin: 14px 7px 0 7px;
		float:left;
	}

	div.photo-item-wrapper:last-of-type {
		padding-bottom: 20px;
	}

	.activity-list .load-more.photo-item-wrapper{
		width: 100%;
	}

	div.photo-item i {
		border-radius: 3px;
		-webkit-transition: opacity 0.04s linear;
		transition: opacity 0.04s linear;
		display: block;
		margin: 0 auto;
		width: 155px;
		height: 155px;
		background-position: 50% 25%;
		background-size: cover;
		background-color: transparent;
		position: relative;
		background-repeat: no-repeat;
	}


</style>
<?php if ( bp_has_activities( bp_ajax_querystring( 'activity' ) ) ) : ?>
	
	<?php if ( empty( $_POST['page'] ) ) : ?>

		<div id="bbmedia-grid-wrapper">
		<ul  class="photo-grid activity-list">

	<?php endif; ?>

	<?php while ( bp_activities() ) : bp_the_activity(); ?>

		<?php bp_get_template_part( 'activity/entry' ); ?>

	<?php endwhile; ?>

	<?php if ( bp_activity_has_more_items() ) : ?>

		<li class="load-more photo-item-wrapper">
			<a href="<?php bp_activity_load_more_link() ?>"><?php _e( 'Load More', 'buddyboss-media' ); ?></a>
		</li>

	<?php endif; ?>

	<?php if ( empty( $_POST['page'] ) ) : ?>

		</ul>

		</div><!-- #bbmedia-grid-wrapper" -->

	<?php endif; ?>


<?php else : ?>

	<div id="message" class="info">
		<p><?php _e( 'Sorry, there were no photos found.', 'buddyboss-media' ); ?></p>
	</div>

<?php endif; ?>

<?php do_action( 'bp_after_activity_loop' ); ?>

<?php if ( empty( $_POST['page'] ) ) : ?>

	<form action="" name="activity-loop-form" id="activity-loop-form" method="post">

		<?php wp_nonce_field( 'activity_filter', '_wpnonce_activity_filter' ); ?>

	</form>

<?php endif; ?>