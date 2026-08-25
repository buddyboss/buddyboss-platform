/**
 * BuddyBoss Integrations marketplace — entry point.
 *
 * Standalone admin page (BuddyBoss → Integrations). Mounts its own React root
 * so the bundle never loads on the Settings page and vice-versa.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

import { createRoot } from '@wordpress/element';
import { App } from './App';

// Styles are compiled separately via the `build:admin:integrations:scss` script
// and enqueued by bb-admin-integrations-page.php — not bundled through webpack.

const container = document.getElementById( 'bb-admin-integrations' );
if ( container ) {
	createRoot( container ).render( <App /> );
}
