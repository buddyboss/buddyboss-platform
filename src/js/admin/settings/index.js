/**
 * BuddyBoss Admin Settings 2.0. - Main entry point.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

import { createRoot } from '@wordpress/element';
import { App } from './App';
import { ErrorBoundary } from './components/ErrorBoundary';

// Register the Tools panel custom field types (Repair Platform + Activation
// Required CTA fallbacks for Sample Data + Migration Tools). Side-effect-only
// import: the file registers `addFilter` handlers on `bb_admin_settings_custom_field`.
import './components/tools';

// Initialize the React app.
const container = document.getElementById( 'bb-admin-settings' );
if ( container ) {
	const root = createRoot( container );
	root.render(
		<ErrorBoundary>
			<App />
		</ErrorBoundary>
	);
}
