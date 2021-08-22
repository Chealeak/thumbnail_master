jQuery(document).ready(function() {
    jQuery('.th_m_disable-button-js').click(function (event) {
        event.preventDefault();

        const thumbnailName = jQuery(this).attr('data-thumbnail-name');
        const disableButton = jQuery('.' + disable_ajax_handler.prefix + 'disable-button-' + thumbnailName + '-js');
        const regenerateSingleButton = jQuery('.th_m_regenerate-single-button-js[data-thumbnail-name="' + thumbnailName + '"]')
        const removeRedundantSingleButton = jQuery('.th_m_remove-redundant-single-button-js[data-thumbnail-name="' + thumbnailName + '"]')

        jQuery.ajax({
            url: disable_ajax_handler.ajaxurl,
            type: 'POST',
            data: 'action=' + disable_ajax_handler.prefix + 'disable_thumbnail&thumbnail_name=' + thumbnailName,
            dataType: 'json',
            beforeSend: function(xhr) {

            },
            success: function(data) {
                if (data['enabled']) {
                    disableButton.text('Disable');
                    disableButton.removeClass('is-info');
                    regenerateSingleButton.removeAttr('disabled');
                    removeRedundantSingleButton.attr('disabled', 'disabled');
                } else {
                    disableButton.text('Enable');
                    disableButton.addClass('is-info');
                    regenerateSingleButton.attr('disabled', 'disabled');
                    removeRedundantSingleButton.removeAttr('disabled');
                }
            }
        });
    });
});