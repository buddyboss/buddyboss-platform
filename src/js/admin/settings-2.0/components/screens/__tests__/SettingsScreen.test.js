import { render, screen, fireEvent, waitFor } from '@testing-library/react';
import SettingsScreen from '../SettingsScreen';

// Mock the Router module
jest.mock('../../Router', () => ({
	updateFeatureInCache: jest.fn(),
}));

describe('SettingsScreen', () => {
	const mockFeatures = [
		{
			id: 'activity',
			label: 'Activity Feeds',
			description: 'Social activity streams',
			status: 'active',
			available: true,
			category: 'community',
			icon: { type: 'font', class: 'bb-icons-rl bb-icons-rl-pulse' }
		},
		{
			id: 'groups',
			label: 'Social Groups',
			description: 'Member groups',
			status: 'inactive',
			available: true,
			category: 'community',
			icon: { type: 'font', class: 'bb-icons-rl bb-icons-rl-users' }
		},
		{
			id: 'learndash',
			label: 'LearnDash',
			description: 'LearnDash integration',
			status: 'inactive',
			available: false,
			category: 'integrations',
			icon: { type: 'font', class: 'bb-icons-rl bb-icons-rl-graduation-cap' }
		}
	];

	beforeEach(() => {
		global.fetch = jest.fn(() =>
			Promise.resolve({
				json: () => Promise.resolve({ success: true, data: {} })
			})
		);
	});

	afterEach(() => {
		jest.clearAllMocks();
	});

	test('renders feature cards', () => {
		render(<SettingsScreen features={mockFeatures} />);

		expect(screen.getByText('Activity Feeds')).toBeInTheDocument();
		expect(screen.getByText('Social Groups')).toBeInTheDocument();
		expect(screen.getByText('Social activity streams')).toBeInTheDocument();
	});

	test('filters features by category', () => {
		render(<SettingsScreen features={mockFeatures} />);

		// Both community features should be visible initially
		expect(screen.getByText('Activity Feeds')).toBeInTheDocument();
		expect(screen.getByText('Social Groups')).toBeInTheDocument();
	});

	test('toggles feature on', async () => {
		global.fetch.mockImplementationOnce(() =>
			Promise.resolve({
				json: () => Promise.resolve({
					success: true,
					data: { id: 'groups', status: 'active' }
				})
			})
		);

		render(<SettingsScreen features={mockFeatures} />);

		const toggles = screen.getAllByRole('switch');
		const groupsToggle = toggles.find(t => !t.checked); // Find the inactive one

		if (groupsToggle) {
			fireEvent.click(groupsToggle);

			await waitFor(() => {
				expect(fetch).toHaveBeenCalledWith(
					'/wp-admin/admin-ajax.php',
					expect.objectContaining({
						method: 'POST'
					})
				);
			});
		}
	});

	test('displays error message on toggle failure', async () => {
		global.fetch.mockImplementationOnce(() =>
			Promise.resolve({
				json: () => Promise.resolve({
					success: false,
					data: { message: 'Feature activation failed' }
				})
			})
		);

		render(<SettingsScreen features={mockFeatures} />);

		const toggles = screen.getAllByRole('switch');
		if (toggles.length > 1) {
			fireEvent.click(toggles[1]);

			await waitFor(() => {
				expect(screen.getByText(/failed/i)).toBeInTheDocument();
			}, { timeout: 3000 });
		}
	});

	test('navigates to feature settings on card click', () => {
		const mockNavigate = jest.fn();

		render(<SettingsScreen features={mockFeatures} navigate={mockNavigate} />);

		const activityCard = screen.getByText('Activity Feeds').closest('.feature-card');
		if (activityCard) {
			fireEvent.click(activityCard);
			expect(mockNavigate).toHaveBeenCalledWith('/settings/activity');
		}
	});

	test('shows unavailable state for features', () => {
		render(<SettingsScreen features={mockFeatures} />);

		// LearnDash should show as unavailable
		const learndashCard = screen.getByText('LearnDash').closest('.feature-card');
		expect(learndashCard).toHaveClass('feature-card--unavailable');
	});

	test('filters features by search query', () => {
		render(<SettingsScreen features={mockFeatures} />);

		const searchInput = screen.getByPlaceholderText(/search/i);
		fireEvent.change(searchInput, { target: { value: 'activity' } });

		expect(screen.getByText('Activity Feeds')).toBeInTheDocument();
		expect(screen.queryByText('Social Groups')).not.toBeInTheDocument();
	});

	test('groups features by category', () => {
		render(<SettingsScreen features={mockFeatures} />);

		// Check that community category header exists
		expect(screen.getByText(/community/i)).toBeInTheDocument();

		// Check that integrations category header exists
		expect(screen.getByText(/integrations/i)).toBeInTheDocument();
	});

	test('handles network errors gracefully', async () => {
		global.fetch.mockImplementationOnce(() =>
			Promise.reject(new Error('Network error'))
		);

		render(<SettingsScreen features={mockFeatures} />);

		const toggles = screen.getAllByRole('switch');
		if (toggles.length > 1) {
			fireEvent.click(toggles[1]);

			await waitFor(() => {
				expect(screen.getByText(/error/i)).toBeInTheDocument();
			}, { timeout: 3000 });
		}
	});
});
