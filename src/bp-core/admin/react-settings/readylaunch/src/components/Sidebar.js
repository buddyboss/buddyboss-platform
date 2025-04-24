import { __ } from '@wordpress/i18n';

export const Sidebar = ( { activeTab, setActiveTab } ) => {
	const menuItems = [
		{
			id: 'activation',
			label: __( 'Activation Settings', 'buddyboss' ),
			icon: 'dashicons-button',
		},
		{
			id: 'styles',
			label: __( 'Styles', 'buddyboss' ),
			icon: 'dashicons-admin-appearance',
		},
		{
			id: 'pages',
			label: __( 'Pages & Sidebars', 'buddyboss' ),
			icon: 'dashicons-admin-page',
		},
		{
			id: 'menus',
			label: __( 'Menus', 'buddyboss' ),
			icon: 'dashicons-menu',
		},
	];

	return (
		<>
			<div className="bb-readylaunch-sidebar">
				<ul>
					{menuItems.map( item => (
						<li
							key={item.id}
							className={activeTab === item.id ? 'active' : ''}
							onClick={() => setActiveTab( item.id )}
						>
							<span className={`dashicons ${item.icon}`}></span>
							{item.label}
						</li>
					) )}
				</ul>
			</div>
		</>
	);
};
