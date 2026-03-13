/**
 * WC Order Status Tracker JavaScript
 */
(function($) {
    'use strict';

    $(document).ready(function() {
        var $form = $('#wc-order-tracker-form');
        var $result = $('#wc-ost-result');
        var $button = $form.find('.wc-ost-button');
        var $buttonText = $form.find('.wc-ost-button-text');
        var $buttonLoader = $form.find('.wc-ost-button-loader');

        $form.on('submit', function(e) {
            e.preventDefault();

            var orderId = $.trim($('#wc_ost_order_id').val());
            var email = $.trim($('#wc_ost_email').val());

            // Basic validation
            if (!orderId || !email) {
                showError(wc_ost_params.strings.not_found);
                return;
            }

            // Email validation
            var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                showError(wc_ost_params.strings.invalid_email);
                return;
            }

            // Show loading state
            setLoading(true);
            $result.hide().removeClass('wc-ost-error');

            // AJAX request
            $.ajax({
                url: wc_ost_params.ajax_url,
                type: 'POST',
                data: {
                    action: 'wc_ost_get_order_status',
                    order_id: orderId,
                    email: email,
                    nonce: wc_ost_params.nonce
                },
                success: function(response) {
                    setLoading(false);

                    if (response.success) {
                        // Hide form and show results
                        $form.slideUp(300);
                        $result.html(response.data.html).slideDown(300);
                    } else {
                        showError(response.data.message || wc_ost_params.strings.not_found);
                    }
                },
                error: function(xhr, status, error) {
                    setLoading(false);
                    showError(wc_ost_params.strings.error);
                    console.error('WC OST Error:', error);
                }
            });
        });

        /**
         * Show error message
         */
        function showError(message) {
            $result.html('<div class="wc-ost-error">' + escapeHtml(message) + '</div>').slideDown(300);
        }

        /**
         * Set loading state
         */
        function setLoading(isLoading) {
            if (isLoading) {
                $button.prop('disabled', true);
                $buttonText.hide();
                $buttonLoader.show();
            } else {
                $button.prop('disabled', false);
                $buttonText.show();
                $buttonLoader.hide();
            }
        }

        /**
         * Escape HTML entities
         */
        function escapeHtml(text) {
            var map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return text.replace(/[&<>"']/g, function(m) { return map[m]; });
        }

        // Clear error when user starts typing
        $('#wc_ost_order_id, #wc_ost_email').on('input', function() {
            if ($result.find('.wc-ost-error').length) {
                $result.slideUp(200);
            }
        });

        // Handle "Track Another Order" button click (delegated for dynamically added content)
        $(document).on('click', '#wc-ost-track-another', function() {
            $result.slideUp(300, function() {
                $form.slideDown(300);
                $form[0].reset();
            });
        });
    });

})(jQuery);
