/**
 * BuddyBoss Admin Settings 2.0. - Main entry point.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

import { render } from '@wordpress/element';
import { App } from './App';
import { ErrorBoundary } from './components/ErrorBoundary';

// Initialize the React app.
const container = document.getElementById( 'bb-admin-settings-2-0' );
if (container) {
	render(
		<ErrorBoundary>
			<App />
		</ErrorBoundary>,
		container
	);
}
