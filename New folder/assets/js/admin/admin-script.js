jQuery(document).ready(function($) {
    var imgInput = $('#loading_image_url'),
        imgFile = $('#loader_image'),
        currentLoaderSrc = imgFile.attr('src'),
        uploadInput = $('#new_loader_image'),
        uploadButton = $('#upload_button');

    uploadButton.click(function() {
        uploadInput.click();
    });

    uploadInput.on('change', function() {
        var _this =  $(this);

        //console.log(_this.get(0).files);

        sendData = new FormData;
        sendData.append('action', 'CJ_WooCommerce_Ajax_Search_upload_loader');
        sendData.append('file', _this.get(0).files[0]);
        sendData.append('nonce', uploadInput.data('uploadNonce'));

        $.ajax({
            url: ajaxurl,
            data: sendData,
            processData: false,
            contentType: false,
            dataType: 'json',
            type: 'POST',
            beforeSend: function() {
                uploadButton.prop('disabled', true);
            },
            success: function (data) {
                if (data.status == 'success') {
                    imgFile.attr('src', data.img);
                    imgInput.val(data.img);

                    uploadButton.next().css('display', 'inline-block');
                } else if (data.status == 'error') {
                    alert(data.message);
                } else {
                    console.log(data);
                }

                uploadButton.prop('disabled', false);
                uploadInput.val('');
            }
        });
    });

    $('#reset_upload_button').on('click', function() {
        var _this = $(this),
            _defaultLoader = imgInput.data('defaultLoader');

        if (imgInput.val() != _defaultLoader) {
            imgFile.attr('src', _defaultLoader);
            imgInput.val(_defaultLoader);
        }

        return false;
    });

    $('#select_where_load_scripts').change(function() {
        var _select = $(this),
            _page_select = _select.closest('tr').next();

        if (_select.val() == 'everywhere') {
            _page_select.hide();
        } else {
            _page_select.show();
        }
    });
});