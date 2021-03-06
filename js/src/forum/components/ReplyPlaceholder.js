/*global s9e*/

import Component from '../../common/Component';
import avatar from '../../common/helpers/avatar';
import username from '../../common/helpers/username';
import DiscussionControls from '../utils/DiscussionControls';
import ComposerPostPreview from './ComposerPostPreview';

/**
 * The `ReplyPlaceholder` component displays a placeholder for a reply, which,
 * when clicked, opens the reply composer.
 *
 * ### Attrs
 *
 * - `discussion`
 */
export default class ReplyPlaceholder extends Component {
  view() {
    if (app.composer.composingReplyTo(this.attrs.discussion)) {
      return (
        <article className="Post CommentPost editing">
          <header className="Post-header">
            <div className="PostUser">
              <h3>
                {avatar(app.session.user, { className: 'PostUser-avatar' })}
                {username(app.session.user)}
              </h3>
            </div>
          </header>
          <ComposerPostPreview className="Post-body" composer={app.composer} surround={this.anchorPreview.bind(this)} />
        </article>
      );
    }

    const reply = () => {
      DiscussionControls.replyAction.call(this.attrs.discussion, true);
    };

    return (
      <article className="Post ReplyPlaceholder" onclick={reply}>
        <header className="Post-header">
          {avatar(app.session.user, { className: 'PostUser-avatar' })} {app.translator.trans('core.forum.post_stream.reply_placeholder')}
        </header>
      </article>
    );
  }

  anchorPreview(preview) {
    const anchorToBottom = $(window).scrollTop() + $(window).height() >= $(document).height();

    preview();

    if (anchorToBottom) {
      $(window).scrollTop($(document).height());
    }
  }
}
