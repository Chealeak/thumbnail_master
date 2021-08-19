jQuery(document).ready(function() {
    jQuery('.th_m_regenerate-button-js').click(regenerate);
    jQuery('.th_m_regenerate-single-button-js').click(regenerate);
});

function regenerate(event) {
    event.preventDefault();

    //var bar = new ldBar("#th_m_progressbar");

    const thumbnailName = jQuery(this).attr('data-thumbnail-name');
    const thumbnailNameActionPart = thumbnailName ? ('&thumbnailName=' + thumbnailName) : '';

    const regenerateButton = jQuery('.th_m_regenerate-button-js');
    const regenerateButtonText = regenerateButton.text();
    const regenerateButtonInProcessText = regenerateButton.data('in-process-text');

    const noticeWrapperHtml = jQuery('.th_m_notices');
    const regenerateSuccessNoticeText = noticeWrapperHtml.data('regenerate-success-text');
    const dismissNoticeText = noticeWrapperHtml.data('dismiss-notice-text')

    jQuery.ajax({
        url: regenerate_ajax_handler.ajaxurl,
        type: 'POST',
        data: 'action=th_m_regenerate_thumbnails' + thumbnailNameActionPart,
        beforeSend: function(xhr) {
            regenerateButton.text(regenerateButtonInProcessText);
        },
        success: function(data) {
            const noticeHtml = "" +
                    "<div class='notice notice-success is-dismissible'>" +
                        "<p>" + regenerateSuccessNoticeText + "</p>" +
                        "<button onclick='this.closest(\".notice\").remove()' type='button' class='notice-dismiss'><span class='screen-reader-text'>" + dismissNoticeText + "</span></button>" +
                    "</div>" +
                "";
            noticeWrapperHtml.append(noticeHtml);
            regenerateButton.text(regenerateButtonText);
        },
/*        xhr: function() {
            var xhr = new XMLHttpRequest();
            xhr.onprogress = function(event) {
                if (event.lengthComputable === false) {
                    bar.set((event.loaded / event.total) * 100);
                } else {
                    bar.set(event.loaded);
                }
            };
            return xhr;
        }*/
    });
}