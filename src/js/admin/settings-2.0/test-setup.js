/**
 * Jest Setup File for Settings 2.0 Tests
 *
 * Sets up WordPress globals and mocks for testing.
 */

// Setup WordPress i18n globals
global.wp = {
	i18n: {
		__: (text) => text,
		sprintf: (text, ...args) => {
			// Simple sprintf implementation for tests
			return text.replace(/%s/g, () => args.shift());
		},
		_n: (single, plural, number) => (number === 1 ? single : plural),
	},
	hooks: {
		addAction: jest.fn(),
		addFilter: jest.fn(),
		applyFilters: jest.fn((hook, value) => value),
		doAction: jest.fn(),
	},
};

// Setup BuddyBoss Admin Data global (used by window.bbAdminData in code)
global.bbAdminData = {
	ajaxNonce: 'test-nonce-12345',
	ajaxUrl: '/wp-admin/admin-ajax.php',
	restUrl: '/wp-json/',
	siteUrl: 'http://example.com',
	logoUrl: 'http://example.com/logo.png',
	currentUser: {
		id: 1,
		name: 'Test Admin',
	},
	features: [],
};

// Alias for backwards compatibility with tests
global.bbAdminSettings = global.bbAdminData;

// Mock fetch API
global.fetch = jest.fn();

// Mock console methods to reduce noise in tests
global.console = {
	...console,
	error: jest.fn(),
	warn: jest.fn(),
	log: jest.fn(),
};

// Setup React Testing Library matchers
import '@testing-library/jest-dom';

// Clean up after each test
afterEach(() => {
	jest.clearAllMocks();
	// Reset fetch mock
	global.fetch.mockReset();
});
