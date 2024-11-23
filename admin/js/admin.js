jQuery(document).ready(function($) {
    // Function to test RPC connection
    function testRPCConnection() {
        const data = {
            action: 'test_neoxa_rpc',
            nonce: neoxaAdmin.nonce
        };

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: data,
            success: function(response) {
                if (response.success) {
                    showNotice('RPC connection successful!', 'success');
                } else {
                    showNotice('RPC connection failed: ' + response.data.message, 'error');
                }
            },
            error: function() {
                showNotice('Failed to test RPC connection. Please try again.', 'error');
            }
        });
    }

    // Function to show admin notices
    function showNotice(message, type) {
        const noticeClass = type === 'success' ? 'notice-success' : 'notice-error';
        const notice = $(`
            <div class="notice ${noticeClass} is-dismissible">
                <p>${message}</p>
                <button type="button" class="notice-dismiss">
                    <span class="screen-reader-text">Dismiss this notice.</span>
                </button>
            </div>
        `);

        $('.wrap h2').first().after(notice);

        // Handle notice dismissal
        notice.find('.notice-dismiss').on('click', function() {
            notice.fadeOut(300, function() { $(this).remove(); });
        });

        // Auto-dismiss after 5 seconds
        setTimeout(function() {
            notice.fadeOut(300, function() { $(this).remove(); });
        }, 5000);
    }

    // Handle asset selection
    $('.neoxa-asset-item input[type="checkbox"]').on('change', function() {
        const $checkbox = $(this);
        const assetName = $checkbox.val();
        
        // Don't allow unchecking NEOXA
        if (assetName === 'NEOXA' && !$checkbox.prop('checked')) {
            $checkbox.prop('checked', true);
            showNotice('NEOXA cannot be disabled as it is the main currency.', 'error');
            return;
        }

        // Visual feedback
        const $item = $checkbox.closest('.neoxa-asset-item');
        if ($checkbox.prop('checked')) {
            $item.addClass('selected');
        } else {
            $item.removeClass('selected');
        }
    });

    // Handle form submission
    $('form').on('submit', function(e) {
        const $form = $(this);
        const requiredFields = ['rpc_host', 'rpc_port', 'rpc_user', 'rpc_password'];
        let hasError = false;

        // Check required fields
        requiredFields.forEach(field => {
            const $field = $(`#${field}`);
            if (!$field.val().trim()) {
                hasError = true;
                $field.addClass('error');
                if (!$field.next('.error-message').length) {
                    $field.after(`<span class="error-message">This field is required.</span>`);
                }
            } else {
                $field.removeClass('error');
                $field.next('.error-message').remove();
            }
        });

        if (hasError) {
            e.preventDefault();
            showNotice('Please fill in all required fields.', 'error');
        }
    });

    // Clear error state on input
    $('input').on('input', function() {
        $(this).removeClass('error');
        $(this).next('.error-message').remove();
    });

    // Add test connection button functionality
    $('#test-rpc-connection').on('click', function(e) {
        e.preventDefault();
        testRPCConnection();
    });

    // Initialize tooltips
    $('[data-tooltip]').each(function() {
        $(this).tooltip({
            content: $(this).data('tooltip'),
            position: { my: 'left+10 center', at: 'right center' }
        });
    });

    // Add copy button functionality for configuration
    $('.copy-config').on('click', function(e) {
        e.preventDefault();
        const configText = $(this).prev('pre').text();
        
        // Create temporary textarea
        const $temp = $('<textarea>');
        $('body').append($temp);
        $temp.val(configText).select();
        
        // Copy text
        try {
            document.execCommand('copy');
            showNotice('Configuration copied to clipboard!', 'success');
        } catch (err) {
            showNotice('Failed to copy configuration. Please try again.', 'error');
        }
        
        // Remove temporary textarea
        $temp.remove();
    });

    // Add responsive menu toggle
    $('.neoxa-menu-toggle').on('click', function() {
        $('.neoxa-admin-sidebar').toggleClass('active');
    });
});
