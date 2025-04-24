import { useState, useEffect, useCallback } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { ToggleControl, TextControl, Spinner, Notice, ColorPicker, RadioControl, Button, SelectControl } from '@wordpress/components';
import { DragDropContext, Droppable, Draggable } from 'react-beautiful-dnd';
import { Sidebar } from './Sidebar';
import { fetchSettings, saveSettings, debounce } from '../utils/api';
import { Accordion } from './Accordion'; // Import the new Accordion component
import { LinkItem } from './LinkItem'; // Import the LinkItem component for menu links
import { LinkModal } from './LinkModal'; // Import the LinkModal component

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
		readyLaunchEnabled: false,
		communityName: '',
		// Style Settings
		enableDarkMode: false,
		lightLogo: null,
		darkLogo: null,
		primaryColorLight: '#3E34FF',
		primaryColorDark: '#9747FF',
		themeMode: 'light',
		// Pages & Sidebars Settings
		enabledPages: {
			registration: true,
			courses: true,
			events: true,
			gamification: true
		},
		activityFeedSidebars: {
			completeProfile: true,
			latestUpdates: true,
			recentBlogPosts: true,
			activeMembers: true
		},
		memberDirectorySidebars: {
			completeProfile: true,
			connections: false,
			myNetwork: false,
			social: false
		},
		memberProfileSidebars: {
			completeProfile: true,
			connections: false,
			myNetwork: false,
			social: false
		},
		groupSidebars: {
			aboutGroup: true,
			groupMembers: false
		},
		// Menu Settings - Keeping the structure for saving/loading compatibility for now
		headerMenu: 'default', // 'default', 'custom'
		sideMenu: { // This might become partially redundant with sideMenuItems but needed for load/save
			activityFeed: true,
			members: true,
			groups: true,
			courses: true,
			messages: false,
			notifications: false
		},
		// customLinks remains an array, suitable for sorting
		customLinks: [
			{
				id: 1,
				title: 'Brand Materials',
				url: 'https://www.buddyboss.com/brand-materials',
				isEditing: false
			},
			{
				id: 2,
				title: 'Resources',
				url: 'https://www.buddyboss.com/documentations',
				isEditing: false
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
			// Convert sideMenuItems array back to sideMenu object for saving
			sideMenu: sideMenuItems.reduce((acc, item) => {
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
			// For now, just update the 'enabled' status based on fetched sideMenu object
			// A more robust solution would involve fetching/saving the order itself.
			setSideMenuItems(prevItems => {
				// If data.sideMenu exists, update enabled status
				if (data.sideMenu) {
					return prevItems.map(item => ({
						...item,
						enabled: data.sideMenu[item.id] !== undefined ? data.sideMenu[item.id] : item.enabled
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
		if (category === 'sideMenu') {
			setSideMenuItems(prevItems =>
				prevItems.map(item =>
					item.id === name ? { ...item, enabled: value } : item
				)
			);
			// Also update the original settings.sideMenu for compatibility if needed immediately
			// setSettings(prevSettings => ({
			// 	...prevSettings,
			// 	sideMenu: { ...prevSettings.sideMenu, [name]: value }
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
				customLinks: prevSettings.customLinks.map(link => 
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
				isEditing: false
			};

			setSettings(prevSettings => ({
				...prevSettings,
				customLinks: [...prevSettings.customLinks, newLink]
			}));
		}
		
		setIsLinkModalOpen(false);
	};

	const handleDeleteLink = (id) => {
		setHasUserMadeChanges(true); // Set flag when user makes a change
		
		setSettings(prevSettings => ({
			...prevSettings,
			// Ensure customLinks exists before filtering
			customLinks: prevSettings.customLinks ? prevSettings.customLinks.filter(link => link.id !== id) : []
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
		if (source.droppableId === 'customLinks') {
			const items = Array.from(settings.customLinks);
			const [reorderedItem] = items.splice(source.index, 1);
			items.splice(destination.index, 0, reorderedItem);
			setSettings(prevSettings => ({
				...prevSettings,
				customLinks: items
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
									checked={settings.readyLaunchEnabled}
									onChange={handleSettingChange( 'readyLaunchEnabled' )}
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
										value={settings.communityName}
										onChange={handleSettingChange( 'communityName' )}
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
										checked={settings.enableDarkMode}
										onChange={handleSettingChange('enableDarkMode')}
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
										value={settings.lightLogo}
										onChange={handleImageUpload('lightLogo')}
										description={__('Recommended size 280px by 80px jpg or png', 'buddyboss')}
									/>
									<ImageSelector
										label={__('Dark', 'buddyboss')}
										value={settings.darkLogo}
										onChange={handleImageUpload('darkLogo')}
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
										<ColorPicker
											color={settings.primaryColorLight}
											onChange={(color) => handleSettingChange('primaryColorLight')(color)}
											enableAlpha={false}
											copyformat='hex'
										/>
									</div>
									<div>
										<label>{__('Primary Color (Dark Mode)', 'buddyboss')}</label>
										<ColorPicker
											color={settings.primaryColorDark}
											onChange={(color) => handleSettingChange('primaryColorDark')(color)}
											enableAlpha={false}
											copyformat='hex'
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
										selected={settings.themeMode}
										options={[
											{ label: __('Light Mode', 'buddyboss'), value: 'light' },
											{ label: __('Dark Mode', 'buddyboss'), value: 'dark' },
											{ label: __('Customer Choice', 'buddyboss'), value: 'choice' },
										]}
										onChange={handleSettingChange('themeMode')}
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
												checked={settings.enabledPages.registration}
												onChange={handleNestedSettingChange('enabledPages', 'registration')}
											/>
										</div>
										<div className="toggle-item">
											<ToggleControl
												label={__('Courses', 'buddyboss')}
												checked={settings.enabledPages.courses}
												onChange={handleNestedSettingChange('enabledPages', 'courses')}
											/>
										</div>
										<div className="toggle-item">
											<ToggleControl
												label={__('Events', 'buddyboss')}
												checked={settings.enabledPages.events}
												onChange={handleNestedSettingChange('enabledPages', 'events')}
											/>
										</div>
										<div className="toggle-item">
											<ToggleControl
												label={__('Gamification', 'buddyboss')}
												checked={settings.enabledPages.gamification}
												onChange={handleNestedSettingChange('enabledPages', 'gamification')}
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
												checked={settings.activityFeedSidebars.completeProfile}
												onChange={handleNestedSettingChange('activityFeedSidebars', 'completeProfile')}
											/>
										</div>
										<div className="toggle-item">
											<ToggleControl
												label={__('Latest Updates', 'buddyboss')}
												checked={settings.activityFeedSidebars.latestUpdates}
												onChange={handleNestedSettingChange('activityFeedSidebars', 'latestUpdates')}
											/>
										</div>
										<div className="toggle-item">
											<ToggleControl
												label={__('Recent Blog Posts', 'buddyboss')}
												checked={settings.activityFeedSidebars.recentBlogPosts}
												onChange={handleNestedSettingChange('activityFeedSidebars', 'recentBlogPosts')}
											/>
										</div>
										<div className="toggle-item">
											<ToggleControl
												label={__('Active Members', 'buddyboss')}
												checked={settings.activityFeedSidebars.activeMembers}
												onChange={handleNestedSettingChange('activityFeedSidebars', 'activeMembers')}
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
												checked={settings.memberDirectorySidebars.completeProfile}
												onChange={handleNestedSettingChange('memberDirectorySidebars', 'completeProfile')}
											/>
										</div>
										<div className="toggle-item">
											<ToggleControl
												label={__('Connections', 'buddyboss')}
												checked={settings.memberDirectorySidebars.connections}
												onChange={handleNestedSettingChange('memberDirectorySidebars', 'connections')}
											/>
										</div>
										<div className="toggle-item">
											<ToggleControl
												label={__('My Network (Follow, Followers)', 'buddyboss')}
												checked={settings.memberDirectorySidebars.myNetwork}
												onChange={handleNestedSettingChange('memberDirectorySidebars', 'myNetwork')}
											/>
										</div>
										<div className="toggle-item">
											<ToggleControl
												label={__('Social', 'buddyboss')}
												checked={settings.memberDirectorySidebars.social}
												onChange={handleNestedSettingChange('memberDirectorySidebars', 'social')}
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
												checked={settings.memberProfileSidebars.completeProfile}
												onChange={handleNestedSettingChange('memberProfileSidebars', 'completeProfile')}
											/>
										</div>
										<div className="toggle-item">
											<ToggleControl
												label={__('Connections', 'buddyboss')}
												checked={settings.memberProfileSidebars.connections}
												onChange={handleNestedSettingChange('memberProfileSidebars', 'connections')}
											/>
										</div>
										<div className="toggle-item">
											<ToggleControl
												label={__('My Network (Follow, Followers)', 'buddyboss')}
												checked={settings.memberProfileSidebars.myNetwork}
												onChange={handleNestedSettingChange('memberProfileSidebars', 'myNetwork')}
											/>
										</div>
										<div className="toggle-item">
											<ToggleControl
												label={__('Social', 'buddyboss')}
												checked={settings.memberProfileSidebars.social}
												onChange={handleNestedSettingChange('memberProfileSidebars', 'social')}
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
												checked={settings.groupSidebars.aboutGroup}
												onChange={handleNestedSettingChange('groupSidebars', 'aboutGroup')}
											/>
										</div>
										<div className="toggle-item">
											<ToggleControl
												label={__('Group Members', 'buddyboss')}
												checked={settings.groupSidebars.groupMembers}
												onChange={handleNestedSettingChange('groupSidebars', 'groupMembers')}
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
												value={settings.headerMenu}
												options={[
													{ label: __('ReadyLaunch (Default)', 'buddyboss'), value: 'default' },
													{ label: __('Custom', 'buddyboss'), value: 'custom' }
												]}
												onChange={handleSettingChange('headerMenu')}
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
																		onChange={(value) => handleNestedSettingChange('sideMenu', item.id)(value)} 
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
										<Droppable droppableId="customLinks">
											{(provided) => (
												<div 
													className="field-input custom-links-wrapper"
													{...provided.droppableProps}
													ref={provided.innerRef}
												>
													{settings.customLinks && settings.customLinks.map((link, index) => ( // Added check for settings.customLinks
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
