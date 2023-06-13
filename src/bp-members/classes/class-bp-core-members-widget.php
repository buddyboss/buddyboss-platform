<?php
/**
 * BuddyPress Members Widget.
 *
 * @package BuddyBoss\Members\Widgets
 * @since BuddyPress 1.0.3
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Members Widget.
 *
 * @since BuddyPress 1.0.3
 */
class BP_Core_Members_Widget extends WP_Widget {

	/**
	 * Constructor method.
	 *
	 * @since BuddyPress 1.5.0
	 */
	public function __construct() {

		// Setup widget name & description.
		$name        = __( '(BB) Members', 'buddyboss' );
		$description = __( 'A dynamic list of recently active, popular, and newest members.', 'buddyboss' );

		// Call WP_Widget constructor.
		parent::__construct(
			false,
			$name,
			array(
				'description'                 => $description,
				'classname'                   => 'widget_bp_core_members_widget buddypress widget',
				'customize_selective_refresh' => true,
			)
		);

		if ( is_customize_preview() || is_active_widget( false, false, $this->id_base ) ) {
			add_action( 'bp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		}
	}

	/**
	 * Enqueue scripts.
	 *
	 * @since BuddyPress 2.6.0
	 */
	public function enqueue_scripts() {
		wp_enqueue_script( 'bp-widget-members' );
	}

	/**
	 * Display the Members widget.
	 *
	 * @since BuddyPress 1.0.3
	 *
	 * @param array $args     Widget arguments.
	 * @param array $instance Widget settings, as saved by the user.
	 *
	 * @see   WP_Widget::widget() for description of parameters.
	 */
	public function widget( $args, $instance ) {
		global $members_template;

		// Get widget settings.
		$settings = $this->parse_settings( $instance );

		/**
		 * Filters the title of the Members widget.
		 *
		 * @since BuddyPress 1.8.0
		 * @since BuddyPress 2.3.0 Added 'instance' and 'id_base' to arguments passed to filter.
		 *
		 * @param string $title    The widget title.
		 * @param array  $settings The settings for the particular instance of the widget.
		 * @param string $id_base  Root ID for all widgets of this type.
		 */
		$title = apply_filters( 'widget_title', $settings['title'], $settings, $this->id_base );
		$title = $settings['link_title'] ? '<a href="' . bp_get_members_directory_permalink() . '">' . $title . '</a>' : $title;

		/**
		 * Filters the separator of the member widget links.
		 *
		 * @since BuddyPress 2.4.0
		 *
		 * @param string $separator Separator string. Default '|'.
		 */
		$separator = apply_filters( 'bp_members_widget_separator', '|' );

		// Output before widget HTMl, title (and maybe content before & after it).
		echo $args['before_widget'] . $args['before_title'] . $title . $args['after_title'];

		// Setup args for querying members.
		$members_args = array(
			'user_id'         => 0,
			'type'            => $settings['member_default'],
			'per_page'        => $settings['max_members'],
			'max'             => false,
			'populate_extras' => true,
			'search_terms'    => false,
			'exclude'         => ( function_exists( 'bp_get_users_of_removed_member_types' ) && ! empty( bp_get_users_of_removed_member_types() ) ) ? bp_get_users_of_removed_member_types() : '',
		);

		if ( empty( $members_args['max'] ) && false !== $members_args['max'] ) {
			$members_args['max'] = 5;
		}

		// Back up the global.
		$old_members_template = $members_template;

		?>

		<?php if ( bp_has_members( $members_args ) ) : ?>

			<div class="item-options" id="members-list-options">
				<a href="<?php bp_members_directory_permalink(); ?>" id="newest-members"
					<?php
					if ( 'newest' === $settings['member_default'] ) :
						?>
						class="selected"<?php endif; ?>><?php esc_html_e( 'Newest', 'buddyboss' ); ?></a>
				<span class="bp-separator" role="separator"><?php echo esc_html( $separator ); ?></span>
				<a href="<?php bp_members_directory_permalink(); ?>" id="recently-active-members"
					<?php
					if ( 'active' === $settings['member_default'] ) :
						?>
						class="selected"<?php endif; ?>><?php esc_html_e( 'Active', 'buddyboss' ); ?></a>

				<?php if ( bp_is_active( 'friends' ) ) : ?>
					<span class="bp-separator" role="separator"><?php echo esc_html( $separator ); ?></span>
					<a href="<?php bp_members_directory_permalink(); ?>" id="popular-members"
						<?php
						if ( 'popular' === $settings['member_default'] ) :
							?>
							class="selected"<?php endif; ?>><?php esc_html_e( 'Popular', 'buddyboss' ); ?></a>

				<?php endif; ?>

			</div>

			<ul id="members-list" class="item-list" aria-live="polite" aria-relevant="all" aria-atomic="true">

				<?php
				while ( bp_members() ) :
					bp_the_member();

					$moderation_class = function_exists( 'bp_moderation_is_user_suspended' ) && bp_moderation_is_user_suspended( $members_template->member->id ) ? 'bp-user-suspended' : '';
					$moderation_class = function_exists( 'bp_moderation_is_user_blocked' ) && bp_moderation_is_user_blocked( $members_template->member->id ) ? $moderation_class . ' bp-user-blocked' : $moderation_class;
					?>

					<li class="vcard">
						<div class="item-avatar">
							<a href="<?php bp_member_permalink(); ?>"  class="<?php echo esc_attr( $moderation_class ); ?>">
								<?php bp_member_avatar(); ?>
								<?php bb_user_presence_html( $members_template->member->id ); ?>
							</a>
						</div>

						<div class="item">
							<div class="item-title fn"><a
										href="<?php bp_member_permalink(); ?>"><?php bp_member_name(); ?></a></div>
							<div class="item-meta">
								<?php if ( 'newest' == $settings['member_default'] ) : ?>
									<span class="activity"
										  data-livestamp="<?php bp_core_iso8601_date( bp_get_member_registered( array( 'relative' => false ) ) ); ?>"><?php bp_member_registered(); ?></span>
								<?php elseif ( 'active' == $settings['member_default'] ) : ?>
									<span class="activity"
										  data-livestamp="<?php bp_core_iso8601_date( bp_get_member_last_active( array( 'relative' => false ) ) ); ?>"><?php bp_member_last_active(); ?></span>
								<?php elseif ( bp_is_active( 'friends' ) ) : ?>
									<span class="activity"><?php bp_member_total_friend_count(); ?></span>
								<?php endif; ?>
							</div>
						</div>
					</li>

				<?php endwhile; ?>

			</ul>

			<?php wp_nonce_field( 'bp_core_widget_members', '_wpnonce-members', false ); ?>

			<input type="hidden" name="members_widget_max" id="members_widget_max"
				   value="<?php echo esc_attr( $settings['max_members'] ); ?>"/>

			<div class="more-block <?php echo ( $members_template->total_member_count > $settings['max_members'] ) ? '' : 'bp-hide'; ?>">
				<a href="<?php bp_members_directory_permalink(); ?>" class="count-more">
					<?php esc_html_e( 'See all', 'buddyboss' ); ?><i class="bb-icon-l bb-icon-angle-right"></i>
				</a>
			</div>

		<?php else : ?>

			<div class="widget-error">
				<?php esc_html_e( 'No one has signed up yet!', 'buddyboss' ); ?>
			</div>

		<?php endif; ?>

		<?php
		echo $args['after_widget'];

		// Restore the global.
		$members_template = $old_members_template;
	}

	/**
	 * Update the Members widget options.
	 *
	 * @since BuddyPress 1.0.3
	 *
	 * @param array $new_instance The new instance options.
	 * @param array $old_instance The old instance options.
	 * @return array $instance The parsed options to be saved.
	 */
	public function update( $new_instance, $old_instance ) {
		$instance = $old_instance;

		$instance['title']          = strip_tags( $new_instance['title'] );
		$instance['max_members']    = strip_tags( $new_instance['max_members'] );
		$instance['member_default'] = strip_tags( $new_instance['member_default'] );
		$instance['link_title']     = ( $new_instance['link_title'] ) ? (bool) $new_instance['link_title'] : false;

		return $instance;
	}

	/**
	 * Output the Members widget options form.
	 *
	 * @since BuddyPress 1.0.3
	 *
	 * @param array $instance Widget instance settings.
	 * @return void
	 */
	public function form( $instance ) {

		// Get widget settings.
		$settings       = $this->parse_settings( $instance );
		$title          = strip_tags( $settings['title'] );
		$max_members    = strip_tags( $settings['max_members'] );
		$member_default = strip_tags( $settings['member_default'] );
		$link_title     = (bool) $settings['link_title'];
		?>

		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>">
				<?php esc_html_e( 'Title:', 'buddyboss' ); ?>
				<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" style="width: 100%" />
			</label>
		</p>

		<p>
			<label for="<?php echo $this->get_field_id( 'link_title' ); ?>">
				<input type="checkbox" name="<?php echo $this->get_field_name( 'link_title' ); ?>" id="<?php echo $this->get_field_id( 'link_title' ); ?>" value="1" <?php checked( $link_title ); ?> />
				<?php esc_html_e( 'Link widget title to Members directory', 'buddyboss' ); ?>
			</label>
		</p>

		<p>
			<label for="<?php echo $this->get_field_id( 'max_members' ); ?>">
				<?php esc_html_e( 'Max members to show:', 'buddyboss' ); ?>
				<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'max_members' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'max_members' ) ); ?>" type="number" value="<?php echo esc_attr( $max_members ); ?>" style="width: 30%" />
			</label>
		</p>

		<p>
			<label for="<?php echo $this->get_field_id( 'member_default' ); ?>"><?php esc_html_e( 'Default members to show:', 'buddyboss' ); ?></label>
			<select name="<?php echo $this->get_field_name( 'member_default' ); ?>" id="<?php echo $this->get_field_id( 'member_default' ); ?>">
				<option value="newest"
				<?php
				if ( 'newest' === $member_default ) :
					?>
					selected="selected"<?php endif; ?>><?php esc_html_e( 'Newest', 'buddyboss' ); ?></option>
				<option value="active"
				<?php
				if ( 'active' === $member_default ) :
					?>
					selected="selected"<?php endif; ?>><?php esc_html_e( 'Active', 'buddyboss' ); ?></option>
				<option value="popular"
				<?php
				if ( 'popular' === $member_default ) :
					?>
					selected="selected"<?php endif; ?>><?php esc_html_e( 'Popular', 'buddyboss' ); ?></option>
			</select>
		</p>

		<?php
	}

	/**
	 * Merge the widget settings into defaults array.
	 *
	 * @since BuddyPress 2.3.0
	 *
	 * @param array $instance Widget instance settings.
	 * @return array
	 */
	public function parse_settings( $instance = array() ) {
		return bp_parse_args(
			$instance,
			array(
				'title'          => __( 'Members', 'buddyboss' ),
				'max_members'    => 5,
				'member_default' => 'active',
				'link_title'     => false,
			),
			'members_widget_settings'
		);
	}
}
