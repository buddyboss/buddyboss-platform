import { useState, useEffect, useCallback } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { ToggleControl, TextControl, Spinner, Notice, ColorPicker, RadioControl, Button, SelectControl, ColorIndicator, Popover } from '@wordpress/components';
import { DragDropContext, Droppable, Draggable } from 'react-beautiful-dnd';
import { Sidebar } from './Sidebar';
import { fetchSettings, saveSettings, debounce } from '../../../utils/api';
import { Accordion } from '../../../components/Accordion';
import { LinkItem } from '../../../components/LinkItem';
import { LinkModal } from '../../../components/LinkModal';

// Initial structure for side menu items
const initialSideMenuItems = [
	{ id: 'activityFeed', label: __('Activity Feed', 'buddyboss'), icon: 'activity-icon', enabled: true },
	{ id: 'members', label: __('Members', 'buddyboss'), icon: 'members-icon', enabled: true },
	{ id: 'groups', label: __('Groups', 'buddyboss'), icon: 'groups-icon', enabled: true },
	{ id: 'courses', label: __('Courses', 'buddyboss'), icon: 'courses-icon', enabled: true },
	{ id: 'messages', label: __('Messages', 'buddyboss'), icon: 'messages-icon', enabled: false },
	{ id: 'notifications', label: __('Notifications', 'buddyboss'), icon: 'notifications-icon', enabled: false },
];

export const ReadyLaunchSettings = () => {
	const [ activeTab, setActiveTab ] = useState( 'activation' );
	const [ settings, setSettings ] = useState( {
		// Activation Settings
		bb_rl_enabled: false,
		blogname: '',
		// Style Settings
		bb_rl_skin_appearance: false,
		bb_rl_light_logo: null,
		bb_rl_dark_logo: null,
		bb_rl_color_light: '#3E34FF',
		bb_rl_color_dark: '#9747FF',
		bb_rl_theme_mode: 'light',
		// Pages & Sidebars Settings
		bb_rl_enabled_pages: {
			registration: true,
			courses: true,
			events: true,
			gamification: true
		},
		bb_rl_activity_sidebars: {
			complete_profile: true,
			latest_updates: true,
			recent_blog_posts: true,
			active_members: true
		},
		bb_rl_member_sidebars: {
			complete_profile: true,
			connections: false,
			my_network: false,
			social: false
		},
		bb_rl_member_profile_sidebars: {
			complete_profile: true,
			connections: false,
			my_network: false,
			social: false
		},
		bb_rl_groups_sidebars: {
			about_group: true,
			group_members: false
		},
		// Menu Settings - Keeping the structure for saving/loading compatibility for now
		bb_rl_header_menu: 'default', // 'default', 'custom'
		bb_rl_side_menu: { // This might become partially redundant with sideMenuItems but needed for load/save
			activity_feed: true,
			members: true,
			groups: true,
			courses: true,
			messages: false,
			notifications: false
		},
		// bb_rl_custom_links remains an array, suitable for sorting
		bb_rl_custom_links: [
			{
				id: 1,
				title: 'Brand Materials',
				url: 'https://www.buddyboss.com/brand-materials',
				is_editing: false
			},
			{
				id: 2,
				title: 'Resources',
				url: 'https://www.buddyboss.com/documentations',
				is_editing: false
			}
		]
	} );
	const [ isLoading, setIsLoading ] = useState( true );
	const [ isSaving, setIsSaving ] = useState( false );
	const [ notification, setNotification ] = useState( null );
	const [ initialLoad, setInitialLoad ] = useState(true);
	const [ hasUserMadeChanges, setHasUserMadeChanges ] = useState(false);
	const [ expandedSections, setExpandedSections ] = useState({
		pages: true,
		sidebars: true,
		menus: true
	});
	const [ isLinkModalOpen, setIsLinkModalOpen ] = useState(false);
	const [ currentEditingLink, setCurrentEditingLink ] = useState(null);
	// State for sortable side menu items
	const [ sideMenuItems, setSideMenuItems ] = useState(initialSideMenuItems);

	// Debounced save function
	const debouncedSave = useCallback(debounce(async (newSettings) => {
		// Don't save if we're still loading or it's the initial load
		if (isLoading || initialLoad) {
			return;
		}
		
		setIsSaving(true);
		
		// Prepare settings for saving, including converting sideMenuItems back to the expected object format
		const settingsToSave = {
			...newSettings,
			// Convert sideMenuItems array back to bb_rl_side_menu object for saving
			bb_rl_side_menu: sideMenuItems.reduce((acc, item) => {
				acc[item.id] = item.enabled;
				return acc;
			}, {}),
			// Potentially save side menu order here if backend supports it
			// sideMenuOrder: sideMenuItems.map(item => item.id) 
		};
		// Remove the array version if not needed in saved data
		delete settingsToSave.sideMenuItems; 

		const data = await saveSettings(settingsToSave); // Save the modified structure
		setIsSaving(false);

		if (data) {
			setNotification({
				status: 'success',
				message: __('Settings saved.', 'buddyboss'),
			});
			// Reset the user changes flag after successful save
			setHasUserMadeChanges(false);
		} else {
			setNotification({
				status: 'error',
				message: __('Error saving settings.', 'buddyboss'),
			});
			setHasUserMadeChanges(false);
		}
		// Auto-dismiss notification
		setTimeout(() => setNotification(null), 3000);
	}, 1000), [sideMenuItems, isLoading, initialLoad]);

	useEffect( () => {
		loadSettings();
	}, [] );

	// Auto-save when settings change (except on initial load)
	useEffect(() => {
		// Only save when:
		// 1. Not the initial load
		// 2. Not currently loading
		// 3. User has made changes
		// 4. Not currently saving
		if (!initialLoad && !isLoading && hasUserMadeChanges && !isSaving) {
			debouncedSave(settings);
		}
	}, [settings, initialLoad, isLoading, hasUserMadeChanges, isSaving, debouncedSave]);

	const loadSettings = async () => {
		setIsLoading( true );
		const data = await fetchSettings();
		if ( data ) {
			// Merge fetched settings with defaults to avoid missing keys
			setSettings(prevSettings => ({ ...prevSettings, ...data }));

			// Initialize sideMenuItems based on fetched data and potentially saved order
			// For now, just update the 'enabled' status based on fetched bb_rl_side_menu object
			// A more robust solution would involve fetching/saving the order itself.
			setSideMenuItems(prevItems => {
				// If data.bb_rl_side_menu exists, update enabled status
				if (data.bb_rl_side_menu) {
					return prevItems.map(item => ({
						...item,
						enabled: data.bb_rl_side_menu[item.id] !== undefined ? data.bb_rl_side_menu[item.id] : item.enabled
					}));
				}
				// If a saved order exists (e.g., data.sideMenuOrder), sort prevItems accordingly here
				return prevItems; 
			});
		}
		setIsLoading( false );
		setInitialLoad(false);
		setHasUserMadeChanges(false); // Reset user changes flag after loading
	};

	// Generic handler for simple value changes
	const handleSettingChange = ( name ) => ( value ) => {
		setHasUserMadeChanges(true); // Set flag when user makes a change
		setSettings( prevSettings => ({ ...prevSettings, [ name ]: value }) );
	};

	// Handler for nested settings (for pages and sidebars)
	const handleNestedSettingChange = (category, name) => (value) => {
		setHasUserMadeChanges(true); // Set flag when user makes a change
		
		// Adjust for the new sideMenuItems array structure
		if (category === 'bb_rl_side_menu') {
			setSideMenuItems(prevItems =>
				prevItems.map(item =>
					item.id === name ? { ...item, enabled: value } : item
				)
			);
			// Also update the original settings.bb_rl_side_menu for compatibility if needed immediately
			// setSettings(prevSettings => ({
			// 	...prevSettings,
			// 	bb_rl_side_menu: { ...prevSettings.bb_rl_side_menu, [name]: value }
			// }));

		} else {
			setSettings(prevSettings => ({
				...prevSettings,
				[category]: {
					...prevSettings[category],
					[name]: value
				}
			}));
		}
	};

	// Toggle section expansion
	const toggleSection = (section) => {
		setExpandedSections(prev => ({
			...prev,
			[section]: !prev[section]
		}));
	};

	// Handle custom link operations
	const handleAddLinkClick = () => {
		setCurrentEditingLink(null);
		setIsLinkModalOpen(true);
	};

	const handleEditLinkClick = (link) => {
		setCurrentEditingLink(link);
		setIsLinkModalOpen(true);
	};

	const handleSaveLink = (linkData) => {
		setHasUserMadeChanges(true); // Set flag when user makes a change
		
		if (currentEditingLink) {
			// Update existing link
			setSettings(prevSettings => ({
				...prevSettings,
				bb_rl_custom_links: prevSettings.bb_rl_custom_links.map(link => 
					link.id === currentEditingLink.id 
						? { ...link, title: linkData.title, url: linkData.url } 
						: link
				)
			}));
		} else {
			// Add new link
			const newLink = {
				id: Date.now(), // Simple unique ID
				title: linkData.title,
				url: linkData.url,
				is_editing: false
			};

			setSettings(prevSettings => ({
				...prevSettings,
				bb_rl_custom_links: [...prevSettings.bb_rl_custom_links, newLink]
			}));
		}
		
		setIsLinkModalOpen(false);
	};

	const handleDeleteLink = (id) => {
		setHasUserMadeChanges(true); // Set flag when user makes a change
		
		setSettings(prevSettings => ({
			...prevSettings,
			// Ensure bb_rl_custom_links exists before filtering
			bb_rl_custom_links: prevSettings.bb_rl_custom_links ? prevSettings.bb_rl_custom_links.filter(link => link.id !== id) : []
		}));
	};

	// Specific handler for image uploads using WordPress media library
	const handleImageUpload = (name) => (imageData) => {
		setHasUserMadeChanges(true); // Set flag when user makes a change
		setSettings(prevSettings => ({ ...prevSettings, [name]: imageData }));
	};

	// Helper function to open the WordPress media library
	const openMediaLibrary = (name, onSelect) => {
		// Check if wp is defined and media is available
		if (typeof window.wp === 'undefined' || !window.wp.media) {
			console.error('WordPress Media API is not available');
			alert('WordPress Media API is not available. Please make sure WordPress Media is properly loaded.');
			return;
		}

		// Create the media frame
		const mediaFrame = window.wp.media({
			title: __('Select or Upload Media', 'buddyboss'),
			button: {
				text: __('Use this media', 'buddyboss'),
			},
			multiple: false,
			library: {
				type: 'image'
			}
		});

		mediaFrame.on('select', function() {
			const attachment = mediaFrame.state().get('selection').first().toJSON();
			const imageData = {
				id: attachment.id,
				url: attachment.url,
				alt: attachment.alt || '',
				title: attachment.title || ''
			};
			onSelect(imageData);
		});

		mediaFrame.open();
	};

	// Component for image selection and preview
	const ImageSelector = ({ label, value, onChange, description }) => {
		return (
			<div className="image-selector-component">
				<label>{label}</label>
				<div className="image-selector-control">
					{value && value.url ? (
						<div className="image-preview-wrapper">
							<img 
								src={value.url} 
								alt={value.alt || ''}
								className="image-preview"
							/>
							<div className="image-actions">
								<Button 
									isDestructive
									onClick={() => onChange(null)}
									className="remove-image-button"
									icon="trash"
								>
									{__('Remove', 'buddyboss')}
								</Button>
								<Button
									isPrimary
									onClick={() => openMediaLibrary(label, onChange)}
									className="change-image-button"
									icon="edit"
								>
									{__('Change', 'buddyboss')}
								</Button>
							</div>
						</div>
					) : (
						<Button 
							variant="secondary"
							onClick={() => openMediaLibrary(label, onChange)}
							className="upload-image-button"
							icon="upload"
						>
							{__('Upload Image', 'buddyboss')}
						</Button>
					)}
					{description && (
						<p className="field-description">{description}</p>
					)}
				</div>
			</div>
		);
	};

	// Component for color picker with popover
	const ColorPickerButton = ({ label, color, onChange }) => {
		const [isPickerOpen, setIsPickerOpen] = useState(false);
		const togglePicker = () => setIsPickerOpen(!isPickerOpen);
		const closePicker = () => setIsPickerOpen(false);
		
		// Ensure we have a valid color value
		const colorValue = color || '#3E34FF'; // Default to blue if no color is set

		return (
			<div className="color-picker-button-component">
				<div className="color-picker-button-wrapper">
					<Button
						className="color-picker-button"
						onClick={togglePicker}
						aria-expanded={isPickerOpen}
						aria-label={__('Select color', 'buddyboss')}
					>
						<div className="color-indicator-wrapper">
							<ColorIndicator colorValue={colorValue} />
						</div>
						<span className="color-picker-value">{colorValue}</span>
					</Button>
					{isPickerOpen && (
						<Popover
							className="color-picker-popover"
							onClose={closePicker}
							position="bottom center"
						>
							<div className="color-picker-popover-content">
								<ColorPicker
									color={colorValue}
									onChange={(newColor) => {
										onChange(newColor);
										// Don't close the popover so the user can continue adjusting
									}}
									enableAlpha={false}
									copyFormat="hex"
								/>
								<div className="color-picker-popover-footer">
									<Button
										variant="primary"
										onClick={closePicker}
									>
										{__('Apply', 'buddyboss')}
									</Button>
								</div>
							</div>
						</Popover>
					)}
				</div>
				{label && <span className="color-picker-label">{label}</span>}
			</div>
		);
	};

	// Drag and Drop Handler
	const onDragEnd = (result) => {
		const { source, destination, draggableId, type } = result;

		// Dropped outside the list
		if (!destination) {
			return;
		}

		// Dropped in the same place
		if (
			destination.droppableId === source.droppableId &&
			destination.index === source.index
		) {
			return;
		}

		setHasUserMadeChanges(true); // Set flag when user makes a change
		
		// Reorder Side Menu Items
		if (source.droppableId === 'sideMenuItems') {
			const items = Array.from(sideMenuItems);
			const [reorderedItem] = items.splice(source.index, 1);
			items.splice(destination.index, 0, reorderedItem);
			setSideMenuItems(items);
		}

		// Reorder Custom Links
		if (source.droppableId === 'bb_rl_custom_links') {
			const items = Array.from(settings.bb_rl_custom_links);
			const [reorderedItem] = items.splice(source.index, 1);
			items.splice(destination.index, 0, reorderedItem);
			setSettings(prevSettings => ({
				...prevSettings,
				bb_rl_custom_links: items
			}));
		}
	};

	const renderContent = () => {
		if ( isLoading ) {
			return (
				<div className="settings-loading">
					<Spinner/>
					<p>{__( 'Loading settings...', 'buddyboss' )}</p>
				</div>
			);
		}

		const commonHeader = (
			<>
				{notification && (
					<Notice
						status={notification.status}
						isDismissible={false}
						className="settings-notice"
					>
						{notification.message}
					</Notice>
				)}
				
				{isSaving && (
					<div className="settings-saving-indicator">
						<Spinner />
						<span>{__( 'Saving...', 'buddyboss' )}</span>
					</div>
				)}
			</>
		);

		switch ( activeTab ) {
			case 'activation':
				return (
					<div className="settings-content">
						{commonHeader}
						<div className="settings-card">
							<div className="settings-toggle-container">
								<div className="toggle-content">
									<h3>ReadyLaunch Enabled</h3>
									<p>Description text goes here explaining RL activation and deactivation logics</p>
								</div>
								<ToggleControl
									checked={settings.bb_rl_enabled}
									onChange={handleSettingChange( 'bb_rl_enabled' )}
								/>
							</div>
						</div>

						<div className="settings-card">
							<div className="settings-header">
								<h3>Site Name</h3>
								<span className="help-icon">?</span>
							</div>
							<hr/>
							<div className="settings-form-field">
								<div className="field-label">
									<label>Community Name</label>
									<p>Description text goes here</p>
								</div>
								<div className="field-input">
									<TextControl
										placeholder="Type community name"
										value={settings.blogname}
										onChange={handleSettingChange( 'blogname' )}
									/>
									<p className="field-description">Description texts goes here</p>
								</div>
							</div>
						</div>
					</div>
				);
			case 'styles':
				return (
					<div className="settings-content">
						{commonHeader}
						<h1>{__('Style Settings', 'buddyboss')}</h1>
						<p className="settings-description">{__('ReadyLaunch loads BuddyBoss templates into your community with minimal customization, making deployment easy.', 'buddyboss')}</p>
						
						<div className="settings-card">
							<div className="settings-header">
								<h3>{__('Branding', 'buddyboss')}</h3>
								<span className="help-icon">?</span>
							</div>
							<hr />

							{/* Appearance Setting */}
							<div className="settings-form-field with-toggle">
								<div className="field-label">
									<label>{__('Appearance', 'buddyboss')}</label>
									<p>{__('Description text goes here', 'buddyboss')}</p>
								</div>
								<div className="field-input">
									<ToggleControl
										label={__('Enable Dark Mode', 'buddyboss')}
										checked={settings.bb_rl_skin_appearance}
										onChange={handleSettingChange('bb_rl_skin_appearance')}
									/>
								</div>
							</div>
							<hr />

							{/* Logo Setting */}
							<div className="settings-form-field">
								<div className="field-label">
									<label>{__('Logo', 'buddyboss')}</label>
									<p>{__('Description text goes here', 'buddyboss')}</p>
								</div>
								<div className="field-input logo-uploaders">
									<ImageSelector
										label={__('Light', 'buddyboss')}
										value={settings.bb_rl_light_logo}
										onChange={handleImageUpload('bb_rl_light_logo')}
										description={__('Recommended size 280px by 80px jpg or png', 'buddyboss')}
									/>
									<ImageSelector
										label={__('Dark', 'buddyboss')}
										value={settings.bb_rl_dark_logo}
										onChange={handleImageUpload('bb_rl_dark_logo')}
										description={__('Recommended size 280px by 80px jpg or png', 'buddyboss')}
									/>
								</div>
							</div>
							<hr />

							{/* Theme Color Setting */}
							<div className="settings-form-field">
								<div className="field-label">
									<label>{__('Theme Color', 'buddyboss')}</label>
									<p>{__('Description text goes here', 'buddyboss')}</p>
								</div>
								<div className="field-input color-palettes">
									<div>
										<label>{__('Primary Color (Light Mode)', 'buddyboss')}</label>
										<ColorPickerButton
											label={__('Primary Color (Light Mode)', 'buddyboss')}
											color={settings.bb_rl_color_light}
											onChange={handleSettingChange('bb_rl_color_light')}
										/>
									</div>
									<div>
										<label>{__('Primary Color (Dark Mode)', 'buddyboss')}</label>
										<ColorPickerButton
											label={__('Primary Color (Dark Mode)', 'buddyboss')}
											color={settings.bb_rl_color_dark}
											onChange={handleSettingChange('bb_rl_color_dark')}
										/>
									</div>
								</div>
							</div>
							<hr />

							{/* Theme Mode Setting */}
							<div className="settings-form-field">
								<div className="field-label">
									<label>{__('Theme Mode Settings', 'buddyboss')}</label>
									<p>{__('Description text goes here', 'buddyboss')}</p>
								</div>
								<div className="field-input">
									<RadioControl
										selected={settings.bb_rl_theme_mode}
										options={[
											{ label: __('Light Mode', 'buddyboss'), value: 'light' },
											{ label: __('Dark Mode', 'buddyboss'), value: 'dark' },
											{ label: __('Customer Choice', 'buddyboss'), value: 'choice' },
										]}
										onChange={handleSettingChange('bb_rl_theme_mode')}
									/>
								</div>
							</div>

						</div>
					</div>
				);
			case 'pages':
				return (
					<div className="settings-content">
						{commonHeader}
						<h1>{__('Pages and Widgets Settings', 'buddyboss')}</h1>
						<p className="settings-description">{__('ReadyLaunch loads BuddyBoss templates into your community with minimal customization, making deployment easy.', 'buddyboss')}</p>
					
						<div className="settings-card">
							<Accordion 
								title={__('Pages', 'buddyboss')}
								isExpanded={expandedSections.pages}
								onToggle={() => toggleSection('pages')}
							>
								<div className="settings-form-field with-multiple-toggles">
									<div className="field-label">
										<label>{__('Enable Pages', 'buddyboss')}</label>
										<p>{__('Select which BuddyBoss pages you would like to use ReadyLaunch style with', 'buddyboss')}</p>
									</div>
									<div className="field-toggles">
										<div className="toggle-item">
											<ToggleControl
												label={__('Registration', 'buddyboss')}
												checked={settings.bb_rl_enabled_pages.registration}
												onChange={handleNestedSettingChange('bb_rl_enabled_pages', 'registration')}
											/>
										</div>
										<div className="toggle-item">
											<ToggleControl
												label={__('Courses', 'buddyboss')}
												checked={settings.bb_rl_enabled_pages.courses}
												onChange={handleNestedSettingChange('bb_rl_enabled_pages', 'courses')}
											/>
										</div>
										<div className="toggle-item">
											<ToggleControl
												label={__('Events', 'buddyboss')}
												checked={settings.bb_rl_enabled_pages.events}
												onChange={handleNestedSettingChange('bb_rl_enabled_pages', 'events')}
											/>
										</div>
										<div className="toggle-item">
											<ToggleControl
												label={__('Gamification', 'buddyboss')}
												checked={settings.bb_rl_enabled_pages.gamification}
												onChange={handleNestedSettingChange('bb_rl_enabled_pages', 'gamification')}
											/>
										</div>
									</div>
								</div>
							</Accordion>
						</div>

						<div className="settings-card">
							<Accordion 
								title={__('Sidebars', 'buddyboss')}
								isExpanded={expandedSections.sidebars}
								onToggle={() => toggleSection('sidebars')}
							>
								{/* Activity Feed */}
								<div className="settings-form-field with-multiple-toggles">
									<div className="field-label">
										<label>{__('Activity Feed', 'buddyboss')}</label>
										<p>{__('Description text goes here', 'buddyboss')}</p>
									</div>
									<div className="field-toggles">
										<div className="toggle-item">
											<ToggleControl
												label={__('Complete Profile', 'buddyboss')}
												checked={settings.bb_rl_activity_sidebars.complete_profile}
												onChange={handleNestedSettingChange('bb_rl_activity_sidebars', 'complete_profile')}
											/>
										</div>
										<div className="toggle-item">
											<ToggleControl
												label={__('Latest Updates', 'buddyboss')}
												checked={settings.bb_rl_activity_sidebars.latest_updates}
												onChange={handleNestedSettingChange('bb_rl_activity_sidebars', 'latest_updates')}
											/>
										</div>
										<div className="toggle-item">
											<ToggleControl
												label={__('Recent Blog Posts', 'buddyboss')}
												checked={settings.bb_rl_activity_sidebars.recent_blog_posts}
												onChange={handleNestedSettingChange('bb_rl_activity_sidebars', 'recent_blog_posts')}
											/>
										</div>
										<div className="toggle-item">
											<ToggleControl
												label={__('Active Members', 'buddyboss')}
												checked={settings.bb_rl_activity_sidebars.active_members}
												onChange={handleNestedSettingChange('bb_rl_activity_sidebars', 'active_members')}
											/>
										</div>
									</div>
								</div>

								{/* Member Directory */}
								<div className="settings-form-field with-multiple-toggles">
									<div className="field-label">
										<label>{__('Member Directory', 'buddyboss')}</label>
										<p>{__('Description text goes here', 'buddyboss')}</p>
									</div>
									<div className="field-toggles">
										<div className="toggle-item">
											<ToggleControl
												label={__('Complete Profile', 'buddyboss')}
												checked={settings.bb_rl_member_sidebars.complete_profile}
												onChange={handleNestedSettingChange('bb_rl_member_sidebars', 'complete_profile')}
											/>
										</div>
										<div className="toggle-item">
											<ToggleControl
												label={__('Connections', 'buddyboss')}
												checked={settings.bb_rl_member_sidebars.connections}
												onChange={handleNestedSettingChange('bb_rl_member_sidebars', 'connections')}
											/>
										</div>
										<div className="toggle-item">
											<ToggleControl
												label={__('My Network (Follow, Followers)', 'buddyboss')}
												checked={settings.bb_rl_member_sidebars.my_network}
												onChange={handleNestedSettingChange('bb_rl_member_sidebars', 'my_network')}
											/>
										</div>
										<div className="toggle-item">
											<ToggleControl
												label={__('Social', 'buddyboss')}
												checked={settings.bb_rl_member_sidebars.social}
												onChange={handleNestedSettingChange('bb_rl_member_sidebars', 'social')}
											/>
										</div>
									</div>
								</div>

								{/* Member Profile */}
								<div className="settings-form-field with-multiple-toggles">
									<div className="field-label">
										<label>{__('Member Profile', 'buddyboss')}</label>
										<p>{__('Description text goes here', 'buddyboss')}</p>
									</div>
									<div className="field-toggles">
										<div className="toggle-item">
											<ToggleControl
												label={__('Complete Profile', 'buddyboss')}
												checked={settings.bb_rl_member_profile_sidebars.complete_profile}
												onChange={handleNestedSettingChange('bb_rl_member_profile_sidebars', 'complete_profile')}
											/>
										</div>
										<div className="toggle-item">
											<ToggleControl
												label={__('Connections', 'buddyboss')}
												checked={settings.bb_rl_member_profile_sidebars.connections}
												onChange={handleNestedSettingChange('bb_rl_member_profile_sidebars', 'connections')}
											/>
										</div>
										<div className="toggle-item">
											<ToggleControl
												label={__('My Network (Follow, Followers)', 'buddyboss')}
												checked={settings.bb_rl_member_profile_sidebars.my_network}
												onChange={handleNestedSettingChange('bb_rl_member_profile_sidebars', 'my_network')}
											/>
										</div>
										<div className="toggle-item">
											<ToggleControl
												label={__('Social', 'buddyboss')}
												checked={settings.bb_rl_member_profile_sidebars.social}
												onChange={handleNestedSettingChange('bb_rl_member_profile_sidebars', 'social')}
											/>
										</div>
									</div>
								</div>

								{/* Group */}
								<div className="settings-form-field with-multiple-toggles">
									<div className="field-label">
										<label>{__('Group', 'buddyboss')}</label>
										<p>{__('Description text goes here', 'buddyboss')}</p>
									</div>
									<div className="field-toggles">
										<div className="toggle-item">
											<ToggleControl
												label={__('About Group', 'buddyboss')}
												checked={settings.bb_rl_groups_sidebars.about_group}
												onChange={handleNestedSettingChange('bb_rl_groups_sidebars', 'about_group')}
											/>
										</div>
										<div className="toggle-item">
											<ToggleControl
												label={__('Group Members', 'buddyboss')}
												checked={settings.bb_rl_groups_sidebars.group_members}
												onChange={handleNestedSettingChange('bb_rl_groups_sidebars', 'group_members')}
											/>
										</div>
									</div>
								</div>
							</Accordion>
						</div>
					</div>
				);
			case 'menus':
				return (
					<div className="settings-content">
						{commonHeader}
						<h1>{__('Menu Settings', 'buddyboss')}</h1>
						<p className="settings-description">{__('ReadyLaunch loads BuddyBoss templates into your community with minimal customization, making deployment easy.', 'buddyboss')}</p>
						
						<DragDropContext onDragEnd={onDragEnd}> {/* Wrap relevant sections */}
							<div className="settings-card">
								<Accordion 
									title={__('Menus', 'buddyboss')}
									isExpanded={expandedSections.menus}
									onToggle={() => toggleSection('menus')}
								>
									{/* Header Menu */}
									<div className="settings-form-field menu-header-field">
										<div className="field-label">
											<label>{__('Header', 'buddyboss')}</label>
											<p>{__('Description text goes here', 'buddyboss')}</p>
										</div>
										<div className="field-input">
											<SelectControl
												value={settings.bb_rl_header_menu}
												options={[
													{ label: __('ReadyLaunch (Default)', 'buddyboss'), value: 'default' },
													{ label: __('Custom', 'buddyboss'), value: 'custom' }
												]}
												onChange={handleSettingChange('bb_rl_header_menu')}
											/>
											<p className="field-note">
												{__('You can update your header menu from the', 'buddyboss')} <strong>Menus</strong> {__('tab, where you will find a dedicated Ready Launch header menu location.', 'buddyboss')}
											</p>
										</div>
									</div>
									
									{/* Side Menu - Now uses Draggable/Droppable */}
									<div className="settings-form-field with-icon-toggles">
										<div className="field-label">
											<label>{__('Side', 'buddyboss')}</label>
											<p>{__('Description text goes here. Drag to reorder.', 'buddyboss')}</p> {/* Added note */}
										</div>
										<Droppable droppableId="sideMenuItems">
											{(provided) => (
												<div 
													className="field-toggles" 
													{...provided.droppableProps} 
													ref={provided.innerRef}
												>
													{sideMenuItems.map((item, index) => (
														<Draggable key={item.id} draggableId={item.id} index={index}>
															{(providedDraggable, snapshot) => (
																<div
																	ref={providedDraggable.innerRef}
																	{...providedDraggable.draggableProps}
																	{...providedDraggable.dragHandleProps}
																	className={`side-menu-item-draggable toggle-item ${snapshot.isDragging ? 'is-dragging' : ''}`}
																>
																	<ToggleControl
																		checked={item.enabled}
																		// Use the updated handler, passing the item ID
																		onChange={(value) => handleNestedSettingChange('bb_rl_side_menu', item.id)(value)} 
																		label={<><span className={`menu-icon ${item.icon}`}></span> {item.label}</>}
																	/>
																</div>
															)}
														</Draggable>
													))}
													{provided.placeholder}
												</div>
											)}
										</Droppable>
									</div>
									
									{/* Custom Links - Now uses Draggable/Droppable */}
									<div className="settings-form-field custom-links-field">
										<div className="field-label">
											<label>{__('Link', 'buddyboss')}</label>
											<p>{__('Description text goes here. Drag to reorder.', 'buddyboss')}</p> {/* Added note */}
										</div>
										<Droppable droppableId="bb_rl_custom_links">
											{(provided) => (
												<div 
													className="field-input custom-links-wrapper"
													{...provided.droppableProps}
													ref={provided.innerRef}
												>
													{settings.bb_rl_custom_links && settings.bb_rl_custom_links.map((link, index) => ( // Added check for settings.bb_rl_custom_links
														<Draggable key={link.id} draggableId={link.id.toString()} index={index}>
															{(providedDraggable, snapshot) => (
																<LinkItem
																	link={link}
																	onEdit={() => handleEditLinkClick(link)}
																	onDelete={() => handleDeleteLink(link.id)}
																	// Pass necessary props from Draggable
																	innerRef={providedDraggable.innerRef}
																	draggableProps={providedDraggable.draggableProps}
																	dragHandleProps={providedDraggable.dragHandleProps}
																	isDragging={snapshot.isDragging} // Pass dragging state for styling
																/>
															)}
														</Draggable>
													))}
													{provided.placeholder}
													
													{/* Add New Link Button - Moved inside Droppable but outside mapping */}
													<Button
														className="add-link-button"
														variant="secondary"
														onClick={handleAddLinkClick}
														icon="plus"
													>
														{__('Add New Link', 'buddyboss')}
													</Button>
												</div>
											)}
										</Droppable>
									</div>
								</Accordion>
							</div>
						</DragDropContext> {/* End DragDropContext */}
						
						{/* Link Modal */}
						<LinkModal
							isOpen={isLinkModalOpen}
							onClose={() => setIsLinkModalOpen(false)}
							onSave={handleSaveLink}
							initialValues={currentEditingLink ? { 
								title: currentEditingLink.title, 
								url: currentEditingLink.url 
							} : { title: '', url: '' }}
						/>
					</div>
				);
			default:
				return <div>Select a tab</div>;
		}
	};

	return (
		<div className="bb-readylaunch-settings-container">
			<Sidebar activeTab={activeTab} setActiveTab={setActiveTab}/>
			<div className="bb-readylaunch-settings-content">
				{renderContent()}
			</div>
		</div>
	);
};
