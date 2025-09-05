import { __ } from '@wordpress/i18n';

export const Header = ({ formData = {}, previewMode = 'light' }) => {
    const lightLogo = formData.bb_rl_light_logo?.url || (typeof formData.bb_rl_light_logo === 'string' ? formData.bb_rl_light_logo : null);
    const darkLogo = formData.bb_rl_dark_logo?.url || (typeof formData.bb_rl_dark_logo === 'string' ? formData.bb_rl_dark_logo : null);
    const blogname = formData.blogname;

    return (
        <div className="bb-rl-preview-page-header">
            <div className="bb-rl-preview-site-title">
                {previewMode === 'dark' && darkLogo ? (
                    <img src={darkLogo} alt={__('Site Logo Dark', 'buddyboss')} />
                ) : previewMode === 'light' && lightLogo ? (
                    <img src={lightLogo} alt={__('Site Logo', 'buddyboss')} />
                ) : (
                    <div className="bb-rl-preview-logo-placeholder">
                        {blogname}
                    </div>
                )}
            </div>

            <ul className="bb-rl-preview-menu">
                <li className="menu-item active">Home</li>
                <li className="menu-item">Groups</li>
                <li className="menu-item">Members</li>
                <li className="menu-item">Courses</li>
                <li className="menu-item">Contact</li>
            </ul>

            <button className="bb-rl-preview-header-search">
                <i className="bb-icons-rl-magnifying-glass"></i>
                <span className="bb-rl-preview-header-search__label">Search community</span>
            </button>

            <span className="bb-rl-preview-header-chat">
                <i className="bb-icons-rl-chat-teardrop-text"></i>
            </span>

            <span className="bb-rl-preview-header-notifications">
                <i className="bb-icons-rl-bell-simple"></i>
            </span>

            <a className="bb-rl-preview-user-img" href="http://localhost:8888/bb/members/john/">
                <img src={`${window.bbRlOnboarding?.assets?.assetsUrl || ''}john.png`} alt="John Muller" />
            </a>
        </div>
    );
}; 