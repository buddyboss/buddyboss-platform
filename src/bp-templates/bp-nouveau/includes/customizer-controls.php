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

		global $bp_nouveau_customizer_nav_group;

		$id      = 'customize-control-' . str_replace( '[', '-', str_replace( ']', '', $this->id ) );
		$class   = 'customize-control customize-control-' . $this->type;
		$hide    = false;
		$id_name = '';
		$setting = "bp_nouveau_appearance[{$this->type}_nav_order]";

		if ( $this->id == $this->type . '_nav_hide' ) {
			$hide = true;
			$id_name = '_hide';
			$setting  = "bp_nouveau_appearance[{$this->type}_nav_hide]";
		}

		$hidden_options = bp_nouveau_get_appearance_settings( "{$this->type}_nav_hide" );
		$item_nav       = array();
		$type           = '';

		// It's a group
		if ( 'group' === $this->type ) {
			$guide = __( 'Customizing the Groups navigation requires you to create a group first.', 'buddyboss' );

			$slug = array();
			$type = 'group';

			if ( isset( $_GET['url'] ) && ! empty( $_GET['url'] ) ) {
				$parse_url = parse_url( $_GET['url'] );
				$path_arr  = explode( '/', $parse_url['path'] );
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
				$guide = __( 'Drag and drop each tab to change the group navigation order.', 'buddyboss' );
			}

			// It's a user!
		} else {
			$item_nav = bp_nouveau_member_customizer_nav();
			$type     = 'user';

			$guide = __( 'Drag and drop each tab to change the profile navigation order.', 'buddyboss' );
		}
		?>

		<?php if ( isset( $guide ) && ! $hide ) : ?>
			<p class="description">
				<?php echo esc_html( $guide ); ?>
			</p>
		<?php endif; ?>

		<?php if ( ! empty( $item_nav ) ) : ?>
			<ul id="<?php echo esc_attr( $id ); ?>" class="ui-sortable <?php echo esc_attr( $id ); ?>" style="margin-top: 0px; height: 500px; <?php echo ( $hide ) ? 'display:none;' : ''; ?>" data-bp-type="<?php echo esc_attr( $this->type ); ?>">

				<?php
				$i = 0;
				foreach ( $item_nav as $item ) :

					// Get current activated theme.
					$theme_name = wp_get_theme();
					$name       = $theme_name->get( 'Name' );

					$checked = '';
					if ( is_array( $hidden_options ) && in_array( $item->slug, $hidden_options ) ) {
						$checked = 'checked="checked"';
					}

					if ( 'user' === $this->type ) {
						$default_tab = 'profile';
						$tab         = bp_nouveau_get_appearance_settings( 'user_default_tab' );
						$default_tab = bp_is_active( $tab ) ? $tab : $default_tab;
					} else {
						$default_tab = 'members';
						$tab 		 = bp_nouveau_get_appearance_settings( 'group_default_tab' );
						$default_tab = bp_is_active( $tab ) ? $tab : $default_tab;
					}

					// Check if theme is BuddyBoss
					if ( strpos( $name, 'BuddyBoss' ) !== false && 'user' === $type ) {

						// If the BuddyBoss theme activated then remove ( Account, Notification abd Message ) tab.
						if ( ! in_array( $item->slug, array( 'settings', 'notifications', 'messages' ) ) ) {

							$i += 1;

							?>
							<li data-bp-nav="<?php echo esc_attr( $item->slug ); ?>" class="<?php echo esc_attr( $item->slug ); ?>">
								<div class="menu-item-bar">
									<div class="menu-item-handle ui-sortable-handle">
									<span class="item-title" aria-hidden="true">
										<span class="menu-item-title"><?php echo esc_html( _bp_strip_spans_from_title( $item->name ) ); ?></span>
											<?php
											$class = '';
											if ( $default_tab === $item->slug ) {
												$class = 'bp-hide';

											} ?>
										<?php if ( $hide ) { ?>
											<span class="checkbox-wrap <?php echo esc_attr( $class ); ?>">
												<input data-bp-hide="<?php echo esc_attr( $item->slug ); ?>" <?php echo $checked; ?> type="checkbox" class="hidden-checkboxes" id="hidden_<?php echo esc_attr( $item->slug ); ?>" name="<?php echo esc_attr( 'hidden_' . $item->slug ); ?>" value="1" data-bp-which-type="<?php echo esc_attr( $this->type ); ?>">
												<label for="hidden_<?php echo esc_attr( $item->slug ); ?>"><?php echo esc_html( __( 'Hide', 'buddyboss') ); ?></label><br>
											</span>
										<?php } else { ?>
											<span class="checkbox-wrap <?php echo esc_attr( $class ); ?>">
												<input data-bp-hide="<?php echo esc_attr( $item->slug ); ?>" <?php echo $checked; ?> type="checkbox" class="visible-checkboxes" id="visible_<?php echo esc_attr( $item->slug ); ?>" name="<?php echo esc_attr( 'visible_' . $item->slug ); ?>" value="1" data-bp-which-type="<?php echo esc_attr( $this->type ); ?>">
												<label for="visible_<?php echo esc_attr( $item->slug ); ?>"><?php echo esc_html( __( 'Hide', 'buddyboss') ); ?></label><br>
											</span>
										<?php } ?>
									</span>
									</div>
								</div>
							</li>
							<?php
						}
						// do nothing
					} else {
						$i += 1;

						?>
						<li data-bp-nav="<?php echo esc_attr( $item->slug ); ?>" class="<?php echo esc_attr( $item->slug ); ?>">
							<div class="menu-item-bar">
								<div class="menu-item-handle ui-sortable-handle">
									<span class="item-title" aria-hidden="true">
										<span class="menu-item-title"><?php echo esc_html( _bp_strip_spans_from_title( $item->name ) ); ?></span>
											<?php
											$class = '';
											if ( $default_tab === $item->slug ) {
												$class = 'bp-hide';

											}
											if ( 'user' === $this->type ) {
												if ( $hide ) { ?>
													<span class="checkbox-wrap <?php echo esc_attr( $class ); ?>">
														<input data-bp-hide="<?php echo esc_attr( $item->slug ); ?>" <?php echo $checked; ?> type="checkbox" class="hidden-checkboxes" id="hidden_<?php echo esc_attr( $item->slug ); ?>" name="<?php echo esc_attr( 'hidden_' . $item->slug ); ?>" value="1" data-bp-which-type="<?php echo esc_attr( $this->type ); ?>">
														<label for="hidden_<?php echo esc_attr( $item->slug ); ?>"><?php echo esc_html( __( 'Hide', 'buddyboss') ); ?></label><br>
													</span> <?php
												} else { ?>
													<span class="checkbox-wrap <?php echo esc_attr( $class ); ?>">
														<input data-bp-hide="<?php echo esc_attr( $item->slug ); ?>" <?php echo $checked; ?> type="checkbox" class="visible-checkboxes" id="visible_<?php echo esc_attr( $item->slug ); ?>" name="<?php echo esc_attr( 'visible_' . $item->slug ); ?>" value="1" data-bp-which-type="<?php echo esc_attr( $this->type ); ?>">
														<label for="visible_<?php echo esc_attr( $item->slug ); ?>"><?php echo esc_html( __( 'Hide', 'buddyboss') ); ?></label><br>
													</span> <?php
												}
											}
											?>
									</span>
								</div>
							</div>
						</li>
						<?php
					}

				endforeach; ?>

			</ul>
		<?php endif; ?>

		<input id="<?php echo esc_attr( 'bp_item_' . $this->type . $id_name ); ?>" type="hidden" value="" data-customize-setting-link="<?php echo esc_attr( $setting ); ?>" />

		<?php
	}
}
