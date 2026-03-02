<?php
/**
 * Profile header actions customizer controls class
 *
 * @since BuddyBoss 1.5.2
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * This control let users change the user profile header actions order of the BuddyBoss
 *
 * NB: this is a first pass to improve by using Javascript templating as explained here:
 * https://developer.wordpress.org/themes/advanced-topics/customizer-api/#putting-the-pieces-together
 *
 * @since BuddyBoss 1.5.2
 */

class BP_Nouveau_Profile_Header_Customize_Control extends WP_Customize_Control {

	/**
	 * @var string
	 */
	public $type = '';

	/**
	 * Render the control's content.
	 *
	 * @since BuddyBoss 1.5.2
	 */
	public function render_content() {
		$id      = 'customize-control-profile-header';
		$hide    = false;
		$setting = "bp_nouveau_appearance[user_profile_actions_order]";

		$order           = bp_nouveau_get_user_profile_actions();
		$profile_buttons = bp_nouveau_customizer_user_profile_actions();

		uksort( $profile_buttons, function ( $key1, $key2 ) use ( $order ) {
			$pos1 = array_search( $key1, $order );
			$pos2 = array_search( $key2, $order );
			if ( false === $pos1 || false === $pos2 ) {
				return 0;
			}
			return $pos1 <=> $pos2; // return spaceship operator comparison result.
		} );

		?>

		<?php if ( isset( $guide ) && ! $hide ) : ?>
			<p class="description">
				<?php echo esc_html( $guide ); ?>
			</p>
		<?php endif; ?>

		<?php if ( ! empty( $profile_buttons ) ) : ?>
			<ul id="<?php echo esc_attr( $id ); ?>" class="ui-sortable <?php echo esc_attr( $id ); ?>"
			    style="margin-top: 0px; height: 500px; <?php echo ( $hide ) ? 'display:none;' : ''; ?>"
			    data-bp-type="<?php echo esc_attr( $this->type ); ?>">

				<?php
				$i = 0;
				foreach ( $profile_buttons as $key => $item ) :
					?>

					<li data-bp-nav="<?php echo esc_attr( $key ); ?>" class="<?php echo esc_attr( $key ); ?>">
						<div class="menu-item-bar">
							<div class="menu-item-handle ui-sortable-handle">
								<span class="item-title" aria-hidden="true">
									<span class="menu-item-title"><?php echo esc_html( $item ); ?></span>
								</span>
							</div>
						</div>
					</li>
				<?php

				endforeach; ?>

			</ul>
		<?php endif; ?>

		<?php
		if ( is_array( bp_nouveau_get_user_profile_actions() ) ) {
			$bp_user_profile_actions_order_value = implode( ',', bp_nouveau_get_user_profile_actions() );
		} else {
			$bp_user_profile_actions_order_value = bp_nouveau_get_user_profile_actions();
		}
		?>
		<input id="bp_user_profile_actions_order" type="hidden"
		       value="<?php echo $bp_user_profile_actions_order_value; ?>"
		       data-customize-setting-link="<?php echo esc_attr( $setting ); ?>"/>

		<?php
	}

}
