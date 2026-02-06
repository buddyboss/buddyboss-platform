/**
 * BuddyBoss Admin Settings 2.0 - Main Entry Point
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss 3.0.0
 */

import { render } from '@wordpress/element';
import { App } from './components/App';

// Initialize the React app
const app = document.getElementById('bb-admin-app');
if (app) {
	render(<App />, app);
}
