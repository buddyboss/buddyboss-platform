import { useState, useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { ToggleControl, TextControl, Spinner, Notice } from '@wordpress/components';
import { Sidebar } from './Sidebar';
import { fetchSettings, saveSettings } from '../utils/api';

export const ReadyLaunchSettings = () => {
	const [ activeTab, setActiveTab ] = useState( 'activation' );
	const [ settings, setSettings ] = useState( {
		readyLaunchEnabled: false,
		communityName: '',
	} );
	const [ isLoading, setIsLoading ] = useState( true );
	const [ isSaving, setIsSaving ] = useState( false );
	const [ notification, setNotification ] = useState( null );
	const [ initialLoad, setInitialLoad ] = useState(true);

	useEffect( () => {
		loadSettings();
	}, [] );

	// Auto-save when settings change (except on initial load)
	useEffect(() => {
		if (!initialLoad && !isLoading) {
			const saveTimer = setTimeout(() => {
				handleSave();
			}, 500); // Debounce save for 500ms

			return () => clearTimeout(saveTimer);
		}
	}, [settings]);

	const loadSettings = async () => {
		setIsLoading( true );
		const data = await fetchSettings();
		if ( data ) {
			setSettings( data );
		}
		setIsLoading( false );
		setInitialLoad(false);
	};

	const handleSave = async () => {
		setIsSaving( true );
		const data = await saveSettings( settings );
		setIsSaving( false );

		if ( data ) {
			setNotification( {
				status: 'success',
				message: __( 'Settings saved successfully!', 'buddyboss' ),
			} );
		} else {
			setNotification( {
				status: 'error',
				message: __( 'Error saving settings. Please try again.', 'buddyboss' ),
			} );
		}

		// Auto-dismiss notification after 3 seconds.
		setTimeout( () => {
			setNotification( null );
		}, 3000 );
	};

	const handleToggleChange = ( name ) => ( value ) => {
		setSettings( { ...settings, [ name ]: value } );
	};

	const handleInputChange = ( name ) => ( value ) => {
		setSettings( { ...settings, [ name ]: value } );
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

		switch ( activeTab ) {
			case 'activation':
				return (
					<div className="settings-content">
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

						<div className="settings-card">
							<div className="settings-toggle-container">
								<div className="toggle-content">
									<h3>ReadyLaunch Enabled</h3>
									<p>Description text goes here explaining RL activation and deactivation logics</p>
								</div>
								<ToggleControl
									checked={settings.readyLaunchEnabled}
									onChange={handleToggleChange( 'readyLaunchEnabled' )}
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
										onChange={handleInputChange( 'communityName' )}
									/>
									<p className="field-description">Description texts goes here</p>
								</div>
							</div>
						</div>
					</div>
				);
			case 'styles':
				return <div>Styles Settings</div>;
			case 'pages':
				return <div>Pages & Sidebars Settings</div>;
			case 'menus':
				return <div>Menus Settings</div>;
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
