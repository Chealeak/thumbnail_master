jQuery(document).ready(function() {
    jQuery('.th_m_regenerate-button-js').click(regenerate);
    jQuery('.th_m_regenerate-single-button-js').click(regenerate);
});

function regenerate(event) {
    event.preventDefault();

    var bar = new ldBar("#th_m_progressbar");

    const thumbnailName = jQuery(this).attr('data-thumbnail-name');
    const thumbnailNameActionPart = thumbnailName ? ('&thumbnailName=' + thumbnailName) : '';

    jQuery.ajax({
        url: regenerate_ajax_handler.ajaxurl,
        type: 'POST',
        data: 'action=th_m_regenerate_thumbnails' + thumbnailNameActionPart,
        beforeSend: function(xhr) {
            jQuery('.th_m_regenerate-button-js').text('Generating...');
        },
        success: function(data) {
            jQuery('.th_m_regenerate-button-js').text('Generate');
        },
        xhr: function() {
            var xhr = new XMLHttpRequest();
            xhr.onprogress = function(event) {
                if (event.lengthComputable === false) {
                    bar.set((event.loaded / event.total) * 100);
                } else {
                    bar.set(event.loaded);
                }
            };
            return xhr;
        }
    });
}