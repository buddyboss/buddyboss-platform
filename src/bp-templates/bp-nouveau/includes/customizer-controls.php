<?php
/**
 * Customizer controls
 *
 * @since BuddyPress 3.0.0
 * @version 3.1.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * This control let users change the order of the BuddyPress
 * single items navigation items.
 *
 * NB: this is a first pass to improve by using Javascript templating as explained here:
 * https://developer.wordpress.org/themes/advanced-topics/customizer-api/#putting-the-pieces-together
 *
 * @since BuddyPress 3.0.0
 */
class BP_Nouveau_Nav_Customize_Control extends WP_Customize_Control {
	/**
	 * @var string
	 */
	public $type = '';

	/**
	 * Render the control's content.
	 *
	 * @since BuddyPress 3.0.0
	 */
	public function render_content() {
		$id       = 'customize-control-' . str_replace( '[', '-', str_replace( ']', '', $this->id ) );
		$class    = 'customize-control customize-control-' . $this->type;
		$setting  = "bp_nouveau_appearance[{$this->type}_nav_order]";
		$item_nav = array();

		// It's a group
		if ( 'group' === $this->type ) {
			$guide = __( 'Customizing the Groups navigation requires you to create a group first.', 'buddyboss' );

			$slug = array();

			if ( isset( $_GET['url'] ) && !empty( $_GET['url'] ) ) {
				$parse_url = parse_url( $_GET['url'] );
				$path_arr = explode( '/', $parse_url['path'] );
				if ( 'groups' === $path_arr[1] && '' !== $path_arr[2] ) {
					$slug = array( $path_arr[2] );
				}
			}

			// Try to fetch any random group:
			$random = groups_get_groups( array(
					'type'        => 'random',
					'per_page'    => 1,
					'slug'        => $slug,
					'show_hidden' => true,
				) );

			if ( ! empty( $random['groups'] ) ) {
				$group    = reset( $random['groups'] );
				$nav      = new BP_Nouveau_Customizer_Group_Nav( $group->id );
				$item_nav = $nav->get_group_nav();
			}

			if ( $item_nav ) {
				$guide = __( 'Drag and drop each group navigation item to change the order, not all groups have every navigation item enabled.', 'buddyboss' );
			}

		// It's a user!
		} else {
			$item_nav = bp_nouveau_member_customizer_nav();

			$guide = __( 'Drag and drop each member navigation item to change the order.', 'buddyboss' );
		}
		?>

		<?php if ( isset( $guide ) ) : ?>
			<p class="description">
				<?php esc_html_e( $guide ); ?>
			</p>
		<?php endif; ?>

		<?php if ( ! empty( $item_nav ) ) : ?>
			<ul id="<?php echo esc_attr( $id ); ?>" class="ui-sortable" style="margin-top: 0px; height: 500px;" data-bp-type="<?php echo esc_attr( $this->type ); ?>">

				<?php
				$i = 0;
				foreach ( $item_nav as $item ) :
					$i += 1;
				?>
					<li data-bp-nav="<?php echo esc_attr( $item->slug ); ?>">
						<div class="menu-item-bar">
							<div class="menu-item-handle ui-sortable-handle">
								<span class="item-title" aria-hidden="true">
									<span class="menu-item-title"><?php esc_html_e( _bp_strip_spans_from_title( $item->name ) ); ?></span>
								</span>
							</div>
						</div>
					</li>
				<?php endforeach; ?>

			</ul>
		<?php endif; ?>

			<input id="<?php echo esc_attr( 'bp_item_' . $this->type ); ?>" type="hidden" value="" data-customize-setting-link="<?php echo esc_attr( $setting ); ?>" />

		<?php
	}
}
