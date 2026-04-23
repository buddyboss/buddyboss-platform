const defaultConfig = require( '@wordpress/scripts/config/jest-unit.config.js' );

module.exports = {
	...defaultConfig,
	testEnvironment: 'jsdom',
	testEnvironmentOptions: {
		url: 'http://localhost',
	},
	setupFilesAfterEnv: [
		'<rootDir>/src/js/admin/settings-2.0/test-setup.js',
	],
	testMatch: [
		'**/__tests__/**/*.[jt]s?(x)',
		'**/?(*.)+(spec|test).[jt]s?(x)',
	],
	collectCoverageFrom: [
		'src/js/admin/settings-2.0/**/*.{js,jsx}',
		'!src/js/admin/settings-2.0/**/__tests__/**',
		'!src/js/admin/settings-2.0/test-setup.js',
	],
	transformIgnorePatterns: [
		'node_modules/(?!(uuid|@wordpress)/)',
	],
	moduleNameMapper: {
		...defaultConfig.moduleNameMapper,
		'\\.(css|less|scss|sass)$': 'identity-obj-proxy',
	},
};
