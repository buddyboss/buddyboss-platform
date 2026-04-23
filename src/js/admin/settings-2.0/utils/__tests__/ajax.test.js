import { ajaxFetch, getFeatures, activateFeature, deactivateFeature, saveFeatureSettings } from '../ajax';

describe('ajax utilities', () => {
	beforeEach(() => {
		global.fetch = jest.fn();
		global.bbAdminData = {
			ajaxNonce: 'test-nonce-12345',
			ajaxUrl: '/wp-admin/admin-ajax.php'
		};
	});

	afterEach(() => {
		jest.clearAllMocks();
	});

	test('ajaxFetch sends correct FormData', async () => {
		global.fetch.mockResolvedValueOnce({
			json: () => Promise.resolve({ success: true })
		});

		await ajaxFetch('test_action', { key: 'value' });

		expect(fetch).toHaveBeenCalledWith(
			'/wp-admin/admin-ajax.php',
			expect.objectContaining({
				method: 'POST',
				body: expect.any(FormData)
			})
		);

		const callArgs = fetch.mock.calls[0];
		const formData = callArgs[1].body;

		// Check FormData contains correct values
		expect(formData.get('action')).toBe('test_action');
		expect(formData.get('nonce')).toBe('test-nonce-12345');
		expect(formData.get('key')).toBe('value');
	});

	test('ajaxFetch handles JSON data objects', async () => {
		global.fetch.mockResolvedValueOnce({
			json: () => Promise.resolve({ success: true })
		});

		await ajaxFetch('test_action', { settings: { opt1: 'val1', opt2: 'val2' } });

		const callArgs = fetch.mock.calls[0];
		const formData = callArgs[1].body;

		const settingsValue = formData.get('settings');
		expect(typeof settingsValue).toBe('string');
		const parsed = JSON.parse(settingsValue);
		expect(parsed.opt1).toBe('val1');
		expect(parsed.opt2).toBe('val2');
	});

	test('getFeatures calls correct action', async () => {
		global.fetch.mockResolvedValueOnce({
			json: () => Promise.resolve({ success: true, data: [] })
		});

		await getFeatures();

		const callArgs = fetch.mock.calls[0];
		const formData = callArgs[1].body;

		expect(formData.get('action')).toBe('bb_admin_get_features');
	});

	test('activateFeature sends feature_id', async () => {
		global.fetch.mockResolvedValueOnce({
			json: () => Promise.resolve({ success: true })
		});

		await activateFeature('test_feature');

		const callArgs = fetch.mock.calls[0];
		const formData = callArgs[1].body;

		expect(formData.get('action')).toBe('bb_admin_activate_feature');
		expect(formData.get('feature_id')).toBe('test_feature');
	});

	test('deactivateFeature sends feature_id', async () => {
		global.fetch.mockResolvedValueOnce({
			json: () => Promise.resolve({ success: true })
		});

		await deactivateFeature('test_feature');

		const callArgs = fetch.mock.calls[0];
		const formData = callArgs[1].body;

		expect(formData.get('action')).toBe('bb_admin_deactivate_feature');
		expect(formData.get('feature_id')).toBe('test_feature');
	});

	test('saveFeatureSettings sends settings data', async () => {
		global.fetch.mockResolvedValueOnce({
			json: () => Promise.resolve({ success: true })
		});

		const settings = {
			option1: 'value1',
			option2: 'value2'
		};

		await saveFeatureSettings('test_feature', settings);

		const callArgs = fetch.mock.calls[0];
		const formData = callArgs[1].body;

		expect(formData.get('action')).toBe('bb_admin_save_feature_settings');
		expect(formData.get('feature_id')).toBe('test_feature');

		const settingsData = JSON.parse(formData.get('settings'));
		expect(settingsData.option1).toBe('value1');
		expect(settingsData.option2).toBe('value2');
	});

	test('handles fetch errors', async () => {
		global.fetch.mockRejectedValueOnce(new Error('Network error'));

		await expect(ajaxFetch('test_action')).rejects.toThrow('Network error');
	});

	test('handles JSON parse errors', async () => {
		global.fetch.mockResolvedValueOnce({
			json: () => Promise.reject(new Error('Invalid JSON'))
		});

		await expect(ajaxFetch('test_action')).rejects.toThrow('Invalid JSON');
	});

	test('handles HTTP errors', async () => {
		global.fetch.mockResolvedValueOnce({
			ok: false,
			status: 500,
			statusText: 'Internal Server Error',
			json: () => Promise.resolve({ success: false, data: { message: 'Server error' } })
		});

		const result = await ajaxFetch('test_action');
		expect(result.success).toBe(false);
	});

	test('includes nonce in all requests', async () => {
		global.fetch.mockResolvedValueOnce({
			json: () => Promise.resolve({ success: true })
		});

		await ajaxFetch('test_action');

		const callArgs = fetch.mock.calls[0];
		const formData = callArgs[1].body;

		expect(formData.get('nonce')).toBe('test-nonce-12345');
	});

	test('uses correct AJAX URL from settings', async () => {
		global.bbAdminData.ajaxUrl = '/custom-admin/admin-ajax.php';

		global.fetch.mockResolvedValueOnce({
			json: () => Promise.resolve({ success: true })
		});

		await ajaxFetch('test_action');

		expect(fetch).toHaveBeenCalledWith(
			'/custom-admin/admin-ajax.php',
			expect.any(Object)
		);

		// Reset for other tests
		global.bbAdminData.ajaxUrl = '/wp-admin/admin-ajax.php';
	});

	test('handles empty data object', async () => {
		global.fetch.mockResolvedValueOnce({
			json: () => Promise.resolve({ success: true })
		});

		await ajaxFetch('test_action', {});

		const callArgs = fetch.mock.calls[0];
		const formData = callArgs[1].body;

		expect(formData.get('action')).toBe('test_action');
		expect(formData.get('nonce')).toBe('test-nonce-12345');
	});
});
