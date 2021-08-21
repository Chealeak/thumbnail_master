jQuery(document).ready(function() {
    jQuery('.th_m_remove-redundant-button-js').click(removeRedundant);
    jQuery('.th_m_remove-redundant-single-button-js').click(removeRedundant);
});

function removeRedundant(event) {
    event.preventDefault();

    const resultElement = jQuery('.' + remove_redundant_ajax_handler.prefix + 'remove-redundant-result-js');
    resultElement.attr('data-page', 1);

    removeRedundantRecursive(event);
}

function removeRedundantRecursive(event) {
    const removeButton = jQuery('.' + remove_redundant_ajax_handler.prefix + 'remove-redundant-button-js');
    const removeButtonText = removeButton.text();
    const removeButtonInProcessText = removeButton.data('in-process-text');
    const resultElement = jQuery('.' + remove_redundant_ajax_handler.prefix + 'remove-redundant-result-js');
    const page = resultElement.attr('data-page');

    const thumbnailName = jQuery(this).attr('data-thumbnail-name');
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
                resultElement.attr('data-page', parseInt(page) + 1);
                removeRedundantRecursive(event);
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