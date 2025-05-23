<?php
declare( strict_types=1 );
/**
 * The template for BP Nouveau Register and Logon page header
 *
 * This template can be overridden by copying it to yourtheme/readylaunch/common/header-register.php.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @version 1.0.0
 */

?>

<header class="bb-rl-login-header">
    <div class="bb-rl-login-header-logo">
    <?php
        $bb_rl_light_logo = bp_get_option( 'bb_rl_light_logo', '' );
        if ( ! empty( $bb_rl_light_logo ) ) {
    ?>
        <img src="<?php echo esc_url( $bb_rl_light_logo['url'] ); ?>" alt="<?php echo esc_attr( $bb_rl_light_logo['title'] ); ?>">
        <style>
            #login h1.wp-login-logo a {
                background-image: url(<?php echo esc_url( $bb_rl_light_logo['url'] ); ?>);
            }
        </style>
    <?php } else {
        $community_name = bp_get_option( 'blogname', '' );
    ?>
        <h2>
        <?php
            if ( ! empty( $community_name ) ) {
                echo esc_html( $community_name );
            } else {
                echo esc_html( get_the_title() );
            }
        ?>
        </h2>
    <?php } ?>
    </div>
    <div class="bb-rl-login-header-actions">
        <?php if( bp_is_register_page() ) : ?>
            <span class="bb-rl-login-header-actions-text"><?php echo esc_html__( 'Already have an account?', 'buddyboss' ); ?></span>
            <a href="<?php echo esc_url( wp_login_url() ); ?>" class="bb-rl-button bb-rl-button--secondary-fill bb-rl-button--small"> <?php echo esc_html__( 'Sign In', 'buddyboss' ); ?></a>
        <?php else : ?>
            <span class="bb-rl-login-header-actions-text"><?php echo esc_html__( 'Don\'t have an account?', 'buddyboss' ); ?></span>
            <a href="<?php echo esc_url( wp_registration_url() ); ?>" class="bb-rl-button bb-rl-button--secondary-fill bb-rl-button--small"> <?php echo esc_html__( 'Sign Up', 'buddyboss' ); ?></a>
        <?php endif; ?>
    </div>
</header>
