import { render } from '@wordpress/element';
import { ReadyLaunchSettings } from './components/ReadyLaunchSettings';

// Initialize the React app
const app = document.getElementById('bb-readylaunch-settings');
if (app) {
    render(<ReadyLaunchSettings />, app);
} 