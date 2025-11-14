<?php
/**
 * BuddyBoss Notifications
 *
 * Wrapper class for managing in-plugin notifications via GroundLevel.
 *
 * @package BuddyBoss\Core\Admin\DRM
 * @since 3.0.0
 */

namespace BuddyBoss\Core\Admin\DRM;

use BuddyBossPlatform\GroundLevel\InProductNotifications\Services\Store;
use BuddyBoss\Core\Admin\Mothership\BB_Mothership_Loader;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Notifications wrapper class.
 */
class BB_Notifications {

	/**
	 * Add an event notification.
	 *
	 * This is NOT for feed notifications. Event notifications are for
	 * alerting the user to something internally (e.g., DRM issues).
	 *
	 * @param array $notification Notification data.
	 */
	public function add( $notification ) {
		if ( empty( $notification['id'] ) ) {
			return;
		}

		// Get the GroundLevel container from the loader.
		$loader    = $this->get_mothership_loader();
		$container = $loader->getContainer();

		/** @var \BuddyBossPlatform\GroundLevel\InProductNotifications\Services\Store $store */
		$store = $container->get( Store::class )->fetch();

		// Format buttons as HTML.
		$btns = array();
		foreach ( $notification['buttons'] as $type => $data ) {
			$btns[] = sprintf(
				'<a class="btn btn--%1$s" href="%2$s" target="%3$s" rel="noopener">%4$s</a>',
				'main' === $type ? 'primary' : 'link',
				esc_url( $data['url'] ),
				esc_attr( $data['target'] ?? '_blank' ),
				esc_html( $data['text'] )
			);
		}

		// Add notification to GroundLevel store.
		$store->add(
			array(
				'id'           => $notification['type'] . '_' . $notification['id'],
				'subject'      => $notification['title'],
				'content'      => $notification['content'] . '<p>' . implode( ' ', $btns ) . '</p>',
				'publishes_at' => gmdate( 'Y-m-d H:i:s', $notification['saved'] ?? time() ),
				'icon'         => sprintf(
					'<img alt="%1$s" src="%2$s" style="width: 100%%; height: auto;">',
					esc_attr__( 'Notification Icon', 'buddyboss' ),
					$notification['icon'] ?? ''
				),
			)
		)->persist();
	}

	/**
	 * Dismiss event notifications by type.
	 *
	 * @param string $type The event type (e.g., 'bb-drm').
	 */
	public function dismiss_events( $type ) {
		// Get the GroundLevel container from the loader.
		$loader    = $this->get_mothership_loader();
		$container = $loader->getContainer();

		/** @var \BuddyBossPlatform\GroundLevel\InProductNotifications\Services\Store $store */
		$store   = $container->get( Store::class )->fetch();
		$persist = false;

		foreach ( $store->notifications( false ) as $notification ) {
			if ( str_starts_with( $notification->id, $type . '_event_' ) ) {
				$store->delete( $notification->id );
				$persist = true;
			}
		}

		if ( $persist ) {
			$store->persist();
		}
	}

	/**
	 * Get the Mothership Loader instance.
	 *
	 * @return BB_Mothership_Loader
	 */
	private function get_mothership_loader() {
		static $loader = null;

		if ( null === $loader ) {
			$loader = new BB_Mothership_Loader();
		}

		return $loader;
	}

	/**
	 * Check if user has access to notifications.
	 *
	 * @return bool
	 */
	public static function has_access() {
		return current_user_can( 'manage_options' );
	}
}
