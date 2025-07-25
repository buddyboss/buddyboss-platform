import { Header } from './Header';
import { LeftSidebar } from './LeftSidebar';
import { Feed } from './Feed';
import { FeedRightSidebar } from './FeedRightSidebar';
import { Members } from './Members';
import { MembersRightSidebar } from './MembersRightSidebar';

export const PreviewPages = ({ page = 'activity' }) => {
    let mainFeed = <Feed />;
    let feedRightSidebar = <FeedRightSidebar />;
    if ( page === 'members' ) {
        mainFeed = <Members />;
        feedRightSidebar = <MembersRightSidebar />;
    }

    return (
        <div className="bb-rl-preview-activity">
            <Header />

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
