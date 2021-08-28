jQuery(document).ready(function() {
    jQuery('.th_m_remove-redundant-button-js').click(removeRedundant);
    jQuery('.th_m_remove-redundant-single-button-js').click(removeRedundant);
});

function removeRedundant(event) {
    event.preventDefault();

    jQuery(this).attr('data-page', 1);
    const thumbnailName = jQuery(this).attr('data-thumbnail-name');

    if (thumbnailName) {
        removeRedundantSingle(jQuery(this), thumbnailName);
    } else {
        removeRedundantAll(jQuery(this));
    }
}

function removeRedundantAll(removeButton) {
    const removeButtonText = removeButton.text();
    const removeButtonInProcessText = removeButton.data('in-process-text');
    const page = removeButton.attr('data-page');
    const noticeWrapperHtml = jQuery('.th_m_notices');
    const removeRedundantSuccessNoticeText = noticeWrapperHtml.data('remove-redundant-success-text');
    const dismissNoticeText = noticeWrapperHtml.data('dismiss-notice-text')

    jQuery.ajax({
        url: remove_redundant_ajax_handler.ajaxurl,
        type: 'POST',
        data: 'action=' + remove_redundant_ajax_handler.prefix + 'remove_redundant_thumbnails&page=' + page,
        dataType: 'json',
        beforeSend: function(xhr) {
            if (parseInt(page) !== 1) {
                removeButton.addClass('is-loading');
                removeButton.text(removeButtonInProcessText);
            }
        },
        success: function(data) {
            if (!data['completed']) {
                removeButton.attr('data-page', parseInt(page) + 1);
                removeRedundantAll(removeButton);
            } else {
                const noticeHtml = "" +
                        "<div class='notice notice-success is-dismissible'>" +
                            "<p>" + removeRedundantSuccessNoticeText + "</p>" +
                            "<button onclick='this.closest(\".notice\").remove()' type='button' class='notice-dismiss'><span class='screen-reader-text'>" + dismissNoticeText + "</span></button>" +
                        "</div>" +
                    "";
                removeButton.removeClass('is-loading');
                removeButton.text(removeButtonText);
                noticeWrapperHtml.append(noticeHtml);
            }
        }
    });
}

function removeRedundantSingle(removeButton, thumbnailName) {
    const removeButtonText = removeButton.text();
    const removeButtonInProcessText = removeButton.data('in-process-text');
    const page = removeButton.attr('data-page');
    const thumbnailNameActionPart = thumbnailName ? ('&thumbnailName=' + thumbnailName) : '';
    const noticeWrapperHtml = jQuery('.th_m_notices');
    const removeRedundantSuccessNoticeText = noticeWrapperHtml.data('remove-redundant-success-text');
    const dismissNoticeText = noticeWrapperHtml.data('dismiss-notice-text')

    jQuery.ajax({
        url: remove_redundant_ajax_handler.ajaxurl,
        type: 'POST',
        data: 'action=' + remove_redundant_ajax_handler.prefix + 'remove_redundant_thumbnails&page=' + page + thumbnailNameActionPart,
        dataType: 'json',
        beforeSend: function(xhr) {
            if (parseInt(page) !== 1) {
                removeButton.text(removeButtonInProcessText);
            }
        },
        success: function(data) {
            if (!data['completed']) {
                removeButton.attr('data-page', parseInt(page) + 1);
                removeRedundantAll(removeButton, thumbnailName);
            } else {
                const noticeHtml = "" +
                    "<div class='notice notice-success is-dismissible'>" +
                    "<p>" + removeRedundantSuccessNoticeText + "</p>" +
                    "<button onclick='this.closest(\".notice\").remove()' type='button' class='notice-dismiss'><span class='screen-reader-text'>" + dismissNoticeText + "</span></button>" +
                    "</div>" +
                    "";
                noticeWrapperHtml.append(noticeHtml);
                removeButton.text(removeButtonText);
            }
        }
    });
}