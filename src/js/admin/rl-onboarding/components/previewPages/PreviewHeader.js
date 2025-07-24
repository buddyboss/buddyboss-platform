export const PreviewHeader = () => {
    return (
        <div className="bb-rl-preview-page-header">
            <div className="bb-rl-preview-site-title">BuddyBoss</div>

            <ul className="bb-rl-preview-menu">
                <li className="menu-item">Home</li>
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
                <img alt="" src="http://localhost:8888/bb/wp-content/uploads/avatars/2/67b8591eb4224-bpthumb.jpg" />
            </a>
        </div>
    );
}; 