import { Header } from './Header';
import { LeftSidebar } from './LeftSidebar';
import { Feed } from './Feed';
import { FeedRightSidebar } from './FeedRightSidebar';
import { Members } from './Members';
import { MembersRightSidebar } from './MembersRightSidebar';

export const PreviewPages = ({ page = 'activity', formData = {} }) => {
    let mainFeed = <Feed />;
    let feedRightSidebar = <FeedRightSidebar />;
    if ( page === 'members' ) {
        mainFeed = <Members />;
        feedRightSidebar = <MembersRightSidebar />;
    }


    // Get the logo URLs from form data (now stored as objects with url property)
    const primaryColor = formData.bb_rl_color_light || formData.brand_colors || '#e57e3a';

    return (
        <div className={`bb-rl-preview-activity ${formData.bb_rl_theme_mode ? 'bb-rl-preview-theme-' + formData.bb_rl_theme_mode : 'bb-rl-preview-theme-light'}`}>
            <style>
                --bb-rl-preview-primary-color: {primaryColor};
            </style>
            <Header formData={formData} />

            <div className="bb-rl-preview-content">
                <LeftSidebar />
                <div className="bb-rl-preview-main">
                    {mainFeed}
                </div>
                {feedRightSidebar}
            </div>
        </div>
    );
};
