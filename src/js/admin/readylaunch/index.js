import { render } from '@wordpress/element';
import { ReadyLaunchSettings } from './components/ReadyLaunchSettings';

// Initialize the React app
const app = document.getElementById('bb-rl-field-wrap');
if (app) {
    render(<ReadyLaunchSettings />, app);
}
