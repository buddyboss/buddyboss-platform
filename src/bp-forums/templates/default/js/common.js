jQuery(document).ready( function() {
    if ( typeof window.Tagify !== 'undefined' ) {
        var input = document.querySelector('input[name=bbp_topic_tags]');
        var tagify = new window.Tagify(input);

        // "remove all tags" button event listener
        var js_modal_close = document.querySelector('.js-modal-close');
        if (typeof js_modal_close !== 'undefined') {
            document.querySelector('.js-modal-close').addEventListener('click', tagify.removeAllTags.bind(tagify));
        }
    }
});