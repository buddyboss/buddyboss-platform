import { render, screen, fireEvent } from '@testing-library/react';
import SettingsForm from '../SettingsForm';

describe('SettingsForm', () => {
	const mockOnChange = jest.fn();

	beforeEach(() => {
		mockOnChange.mockClear();
	});

	test('renders toggle field correctly', () => {
		const field = {
			name: 'test_toggle',
			type: 'toggle',
			label: 'Test Toggle',
			description: 'Test description'
		};

		const settings = { test_toggle: '1' };

		render(
			<SettingsForm
				fields={[field]}
				settings={settings}
				onChange={mockOnChange}
			/>
		);

		expect(screen.getByText('Test Toggle')).toBeInTheDocument();
		expect(screen.getByText('Test description')).toBeInTheDocument();
		expect(screen.getByRole('switch')).toBeInTheDocument();
		expect(screen.getByRole('switch')).toBeChecked();
	});

	test('toggle field onChange updates value', () => {
		const field = {
			name: 'test_toggle',
			type: 'toggle',
			label: 'Test Toggle'
		};

		const settings = { test_toggle: '0' };

		render(
			<SettingsForm
				fields={[field]}
				settings={settings}
				onChange={mockOnChange}
			/>
		);

		const toggle = screen.getByRole('switch');
		fireEvent.click(toggle);

		expect(mockOnChange).toHaveBeenCalledWith('test_toggle', '1');
	});

	test('renders notice field with warning type', () => {
		const field = {
			type: 'notice',
			notice_type: 'warning',
			description: 'This is a warning message'
		};

		render(<SettingsForm fields={[field]} settings={{}} />);

		expect(screen.getByText('This is a warning message')).toBeInTheDocument();
		const notice = screen.getByText('This is a warning message').closest('div');
		expect(notice).toHaveClass('bb-admin-settings-notice--warning');
	});

	test('renders text field with prefix and suffix', () => {
		const field = {
			name: 'test_text',
			type: 'text',
			label: 'Test Text',
			prefix: '$',
			suffix: 'USD'
		};

		const settings = { test_text: '100' };

		render(
			<SettingsForm
				fields={[field]}
				settings={settings}
				onChange={mockOnChange}
			/>
		);

		expect(screen.getByText('$')).toBeInTheDocument();
		expect(screen.getByText('USD')).toBeInTheDocument();
		expect(screen.getByDisplayValue('100')).toBeInTheDocument();
	});

	test('child fields appear when parent condition is met', () => {
		const fields = [
			{
				name: 'parent_toggle',
				type: 'toggle',
				label: 'Parent Toggle'
			},
			{
				name: 'child_field',
				type: 'text',
				label: 'Child Field',
				parent_field: 'parent_toggle',
				parent_value: '1'
			}
		];

		// Parent off - child hidden
		const { rerender } = render(
			<SettingsForm
				fields={fields}
				settings={{ parent_toggle: '0' }}
				onChange={mockOnChange}
			/>
		);

		expect(screen.queryByText('Child Field')).not.toBeInTheDocument();

		// Parent on - child visible
		rerender(
			<SettingsForm
				fields={fields}
				settings={{ parent_toggle: '1' }}
				onChange={mockOnChange}
			/>
		);

		expect(screen.getByText('Child Field')).toBeInTheDocument();
	});

	test('renders toggle_list field with multiple options', () => {
		const field = {
			name: 'test_toggle_list',
			type: 'toggle_list',
			label: 'Enable Reactions',
			options: [
				{ label: 'Activity Posts', value: 'activity' },
				{ label: 'Activity Comments', value: 'activity_comment' }
			]
		};

		const settings = {
			test_toggle_list: {
				activity: '1',
				activity_comment: '0'
			}
		};

		render(
			<SettingsForm
				fields={[field]}
				settings={settings}
				onChange={mockOnChange}
			/>
		);

		expect(screen.getByText('Activity Posts')).toBeInTheDocument();
		expect(screen.getByText('Activity Comments')).toBeInTheDocument();

		const toggles = screen.getAllByRole('switch');
		expect(toggles).toHaveLength(2);
		expect(toggles[0]).toBeChecked();
		expect(toggles[1]).not.toBeChecked();
	});

	test('renders select field with options', () => {
		const field = {
			name: 'test_select',
			type: 'select',
			label: 'Test Select',
			options: [
				{ label: 'Option 1', value: 'opt1' },
				{ label: 'Option 2', value: 'opt2' }
			]
		};

		const settings = { test_select: 'opt1' };

		render(
			<SettingsForm
				fields={[field]}
				settings={settings}
				onChange={mockOnChange}
			/>
		);

		expect(screen.getByText('Test Select')).toBeInTheDocument();
		const select = screen.getByRole('combobox');
		expect(select.value).toBe('opt1');
	});

	test('renders textarea field', () => {
		const field = {
			name: 'test_textarea',
			type: 'textarea',
			label: 'Test Textarea',
			placeholder: 'Enter text here'
		};

		const settings = { test_textarea: 'Some text content' };

		render(
			<SettingsForm
				fields={[field]}
				settings={settings}
				onChange={mockOnChange}
			/>
		);

		expect(screen.getByText('Test Textarea')).toBeInTheDocument();
		expect(screen.getByPlaceholderText('Enter text here')).toBeInTheDocument();
		expect(screen.getByDisplayValue('Some text content')).toBeInTheDocument();
	});

	test('handles text field changes', () => {
		const field = {
			name: 'test_text',
			type: 'text',
			label: 'Test Text'
		};

		const settings = { test_text: '' };

		render(
			<SettingsForm
				fields={[field]}
				settings={settings}
				onChange={mockOnChange}
			/>
		);

		const input = screen.getByRole('textbox');
		fireEvent.change(input, { target: { value: 'new value' } });

		expect(mockOnChange).toHaveBeenCalledWith('test_text', 'new value');
	});
});
