<?php
/**
 * BuddyBoss Notification System Header.
 *
 * Header notify customers about major releases, significant changes, or special offers.
 *
 * @package BuddyBoss
 * @since BuddyBoss 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

?>
<div class="bb-notice-header-wrapper">

	<div class="bb-admin-header">
        <div class="bb-admin-header__logo">
            <img alt="" class="gravatar" src="<?php echo buddypress()->plugin_url; ?>bp-core/images/admin/bb-logo.png" />
        </div>
        <div class="bb-admin-header__nav">
            <div class="bb-admin-nav">
                <div class="bb-notifications-wrapepr">
                    <a href="" class="bb-admin-nav__button bb-admin-nav__notice">
                        <i class="bb-icon-l bb-icon-bell"></i>
                    </a>
                    <div class="bb-notifications-panel">
                        <div class="panel-header"></div>
                        <div class="panel-nav"></div>
                        <div class="panel-body"></div>
                    </div>
                </div>
                <a href="" class="bb-admin-nav__button bb-admin-nav__help"><i class="bb-icon-l bb-icon-question"></i></a>
            </div>
        </div>
    </div>

</div>
