<?php
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
        <img src="<?php echo esc_url( buddypress()->plugin_url . 'bp-templates/bp-nouveau/readylaunch/images/logo.png' ); ?>" alt="<?php esc_attr__( 'ReadyLaunch Logo', 'buddyboss' ) ?>">
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
