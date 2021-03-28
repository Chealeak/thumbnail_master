jQuery(document).ready(function() {
    jQuery('.th_m_disable-button-js').click(function (event) {
        event.preventDefault();

        const thumbnailName = jQuery(this).attr('data-thumbnail-name');
        const disableButton = jQuery('.' + remove_redundant_ajax_handler.prefix + 'disable-button-' + thumbnailName + '-js');

        jQuery.ajax({
            url: disable_ajax_handler.ajaxurl,
            type: 'POST',
            data: 'action=' + disable_ajax_handler.prefix + 'disable_thumbnail&thumbnail_name=' + thumbnailName,
            dataType: 'json',
            beforeSend: function(xhr) {

            },
            success: function(data) {
                if (data['status']) {
                    if (data['status'] === 'enabled') {
                        disableButton.text('Disable');
                    } else {
                        disableButton.text('Enable');
                    }
                }
            }
        });
    });
});