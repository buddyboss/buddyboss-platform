import { __ } from '@wordpress/i18n';

export const Sidebar = ( { activeTab, setActiveTab } ) => {
	const menuItems = [
		{
			id: 'activation',
			label: __( 'Activation Settings', 'buddyboss' ),
			icon: 'toggle-right',
		},
		{
			id: 'styles',
			label: __( 'Styles', 'buddyboss' ),
			icon: 'palette',
		},
		{
			id: 'pages',
			label: __( 'Pages & Sidebars', 'buddyboss' ),
			icon: 'file-text',
		},
		{
			id: 'menus',
			label: __( 'Menus', 'buddyboss' ),
			icon: 'list-dashes',
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
							<i className={`bb-icons-rl-${item.icon}`}></i>
							{item.label}
						</li>
					) )}
				</ul>
			</div>
		</>
	);
};
