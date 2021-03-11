jQuery(document).ready(function() {
    jQuery('.disable-js').click(function (event) {
        event.preventDefault();

        let thumbnailName = jQuery(this).attr('data-thumbnail-name');

        jQuery.ajax({
            url: disable_ajax_handler.ajaxurl,
            type: 'POST',
            data: 'action=' + disable_ajax_handler.prefix + 'disable_thumbnail&thumbnail_name=' + thumbnailName,
            dataType: 'json',
            beforeSend: function(xhr) {

            },
            success: function(data) {

            }
        });
    });
});