jQuery(document).ready(function($) {
    'use strict';

    $.fn.cjWcAjaxSearch = function(options) {

        // Объект ajax-запроса
        var xhr;

        options = $.extend({
            str_length: 3,
            loader_image: 'http://' + location.hostname + '/wp-content/plugins/cj-wc-ajax-search/assets/images/ajax-loader.gif'
        }, options);

        var init = function() {
            var _input = $(this);

            _input.attr('placeholder', options.placeholder);
            _input.wrap('<div class="cjAjaxSearchWrap"></div>');
            _input.after('<div id="cjAjaxSearch_result" class="cjAjaxSearch_products"></div>');

            var _result = _input.next();

            _input.on('keyup', function() {
                var _search = $(this).val();

                _result.hide();

                if (_search.length >= options.str_length) {
                    var sendData = {};

                    sendData.action = 'CJ_WooCommerce_Ajax_Search_process';
                    sendData.search_string = _search;
                    sendData.nonce = options.nonce;

                    // Если у нас запущен другой ajax-запрос, отменяем его
                    if (xhr !== undefined && xhr.readyState == 1) xhr.abort();

                    xhr = $.ajax({
                        url: cj_wc_ajax_search_obj.ajaxurl,
                        data: sendData,
                        dataType: 'json',
                        type: 'POST',
                        beforeSend: function() {
                            _input.css('background-image', 'url(' + options.loader_image + ')');
                        },
                        success: function (data) {
                            if (data.status == 'success') {
                                if (data.products_count > 0) {
                                    _result.html(data.html);
                                    _result.show();
                                } else {
                                    _result.html(data.html);
                                    _result.show();
                                }
                            } else {
                                console.log(data);
                            }

                            _input.removeAttr('style');
                        }
                    });
                }
            });

            _result.on('click', '.cjAjaxSearch_product__addToCart', function() {
                var _button = $(this),
                    _box = _button.parent().parent(),
                    sendData = {};

                sendData.action = 'CJ_WooCommerce_Ajax_Search_add_to_cart';
                sendData.product_id = _button.data('productId');
                sendData.qty = _button.prev().val();

                $.ajax({
                    url: cj_wc_ajax_search_obj.ajaxurl,
                    data: sendData,
                    dataType: 'json',
                    type: 'POST',
                    beforeSend: function() {
                        _button.prop('disabled', true);
                        _button.addClass('loading');
                    },
                    success: function (data) {
                        if (data.status == 'success') {
                           // Сюда пришли фрагменты
                            update_fragments(data.fragments);
                            var _tooltip = _box.find('.cjAjaxSearch_product__button_tooltip');
                            _tooltip.html(data.message);

                            _tooltip.stop().fadeIn(600, function() {
                                setTimeout(function() {
                                    _tooltip.stop().fadeOut(600);
                                }, 2000);
                            });
                        } else {
                            console.log(data);
                        }

                        _button.prop('disabled', false);
                        _button.removeClass('loading');
                    }
                });
            });

            _result.on('click', '.cjAjaxSearch_product__deleteFromCart', function() {
                var _button = $(this),
                    _box = _button.parent().parent(),
                    sendData = {};

                sendData.action = 'CJ_WooCommerce_Ajax_Search_remove_from_cart';
                sendData.product_id = _button.data('productId');
                sendData.cart_item_key = _button.data('cartItemKey');
                sendData.qty = _button.prev().val();

                $.ajax({
                    url: cj_wc_ajax_search_obj.ajaxurl,
                    data: sendData,
                    dataType: 'json',
                    type: 'POST',
                    beforeSend: function() {
                        _button.prop('disabled', true);
                        _button.addClass('loading');
                    },
                    success: function (data) {
                        if (data.status == 'success') {
                            // Сюда пришли фрагменты
                            update_fragments(data.fragments);

                            var _tooltip = _box.find('.cjAjaxSearch_product__button_tooltip');
                            _tooltip.html(data.message);

                            _tooltip.stop().fadeIn(600, function() {
                                setTimeout(function() {
                                    _tooltip.stop().fadeOut(600);
                                }, 2000);
                            });
                        } else {
                            console.log(data);
                        }

                        _button.prop('disabled', false);
                        _button.removeClass('loading');
                    }
                });
            });

            $(document).click(function(e) {
                if ($(e.target).closest("#cjAjaxSearch_result").length) return;

                _result.hide();
            });
        };

        var update_fragments = function(fragments) {
            if (fragments) {
                $.each(fragments, function(key) {
                    $(key).addClass('updating');
                });

                $.each(fragments, function(key, html) {
                    $(key).replaceWith(html);
                });
            }
        };

        return this.each(init);
    };

    var ajaxInputs = $('.cjAjaxInput, .woocommerce-product-search');
    if (ajaxInputs.length > 0) ajaxInputs.cjWcAjaxSearch(cj_wc_ajax_search_obj.settings);


});
