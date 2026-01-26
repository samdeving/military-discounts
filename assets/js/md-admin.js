/**
 * Military Discounts - Admin JavaScript
 */

(function($) {
    'use strict';

    // Form Builder tabs
    $(document).on('click', '.md-builder-tab', function() {
        var tab = $(this).data('tab');
        
        $('.md-builder-tab').removeClass('active');
        $(this).addClass('active');
        
        $('.md-builder-panel').removeClass('active');
        $('.md-builder-panel[data-panel="' + tab + '"]').addClass('active');
    });

    // Initialize sortable for form builder
    function initSortable() {
        $('.md-field-list').sortable({
            handle: '.md-field-handle',
            placeholder: 'md-field-item ui-sortable-placeholder',
            revert: 200
        });
    }
    
    $(document).ready(function() {
        initSortable();
    });

    // Add field
    $(document).on('click', '.md-add-field', function() {
        var formType = $(this).data('form');
        var list = $('#md-' + formType + '-fields');
        var template = $('#md-field-template').html();
        var index = list.find('.md-field-item').length;
        
        template = template.replace(/\{\{index\}\}/g, index);
        list.append(template);
    });

    // Remove field
    $(document).on('click', '.md-remove-field', function() {
        if (confirm('Are you sure you want to remove this field?')) {
            $(this).closest('.md-field-item').fadeOut(200, function() {
                $(this).remove();
            });
        }
    });

    // Save fields
    $(document).on('click', '.md-save-fields', function() {
        var button = $(this);
        var formType = button.data('form');
        var list = $('#md-' + formType + '-fields');
        var fields = [];

        list.find('.md-field-item').each(function() {
            var item = $(this);
            fields.push({
                id: item.find('.md-field-id').val(),
                type: item.find('.md-field-type').val(),
                label: item.find('.md-field-label').val(),
                placeholder: item.find('.md-field-placeholder').val(),
                required: item.find('.md-field-required').is(':checked'),
                api_field: item.find('.md-field-api').val()
            });
        });

        button.prop('disabled', true).text(mdAdmin.strings.saving);

        $.ajax({
            url: mdAdmin.ajaxUrl,
            type: 'POST',
            data: {
                action: 'md_save_form_fields',
                nonce: mdAdmin.nonce,
                form_type: formType,
                fields: fields
            },
            success: function(response) {
                if (response.success) {
                    alert(response.data);
                } else {
                    alert(response.data);
                }
            },
            error: function() {
                alert('Error saving fields.');
            },
            complete: function() {
                button.prop('disabled', false).text('Save Fields');
            }
        });
    });

    // Reset fields
    $(document).on('click', '.md-reset-fields', function() {
        if (!confirm('Are you sure you want to reset to default fields?')) {
            return;
        }

        var button = $(this);
        var formType = button.data('form');

        $.ajax({
            url: mdAdmin.ajaxUrl,
            type: 'POST',
            data: {
                action: 'md_reset_form_fields',
                nonce: mdAdmin.nonce,
                form_type: formType
            },
            success: function(response) {
                if (response.success) {
                    alert(response.data.message);
                    location.reload();
                } else {
                    alert(response.data);
                }
            },
            error: function() {
                alert('Error resetting fields.');
            }
        });
    });

    // Test API connection
    $(document).on('click', '#md-test-api', function() {
        var button = $(this);
        var result = $('#md-test-result');

        button.prop('disabled', true).text(mdAdmin.strings.testingApi);
        result.removeClass('success error').hide();

        $.ajax({
            url: mdAdmin.ajaxUrl,
            type: 'POST',
            data: {
                action: 'md_test_api',
                nonce: mdAdmin.nonce
            },
            success: function(response) {
                if (response.success) {
                    result.addClass('success').text(response.data).show();
                } else {
                    result.addClass('error').text(response.data).show();
                }
            },
            error: function() {
                result.addClass('error').text('Error testing API connection.').show();
            },
            complete: function() {
                button.prop('disabled', false).text('Test Connection');
            }
        });
    });

    // Clear logs
    $(document).on('click', '#md-clear-logs', function() {
        if (!confirm(mdAdmin.strings.confirmClearLogs)) {
            return;
        }

        var button = $(this);
        button.prop('disabled', true).text(mdAdmin.strings.clearingLogs);

        $.ajax({
            url: mdAdmin.ajaxUrl,
            type: 'POST',
            data: {
                action: 'md_clear_logs',
                nonce: mdAdmin.nonce
            },
            success: function(response) {
                if (response.success) {
                    alert(mdAdmin.strings.logsCleared);
                    location.reload();
                } else {
                    alert(response.data);
                }
            },
            error: function() {
                alert(mdAdmin.strings.errorClearing);
            },
            complete: function() {
                button.prop('disabled', false).text('Clear All Logs');
            }
        });
    });

    // Export logs
    $(document).on('click', '#md-export-logs', function() {
        $.ajax({
            url: mdAdmin.ajaxUrl,
            type: 'POST',
            data: {
                action: 'md_export_logs',
                nonce: mdAdmin.nonce
            },
            success: function(response) {
                if (response.success) {
                    var blob = new Blob([response.data.logs], { type: 'application/json' });
                    var url = window.URL.createObjectURL(blob);
                    var a = document.createElement('a');
                    a.href = url;
                    a.download = 'military-discounts-logs-' + new Date().toISOString().slice(0, 10) + '.json';
                    document.body.appendChild(a);
                    a.click();
                    window.URL.revokeObjectURL(url);
                    document.body.removeChild(a);
                }
            }
        });
    });

    // Cancel pending verification
    $(document).on('click', '.md-cancel-verification', function() {
        var button = $(this);
        var user_id = button.data('user-id');
        var row = button.closest('tr');

        if (!confirm(mdAdmin.strings.confirmCancelVerification)) {
            return;
        }

        button.prop('disabled', true).text(mdAdmin.strings.cancelling);

        $.ajax({
            url: mdAdmin.ajaxUrl,
            type: 'POST',
            data: {
                action: 'md_cancel_pending_verification',
                nonce: mdAdmin.nonce,
                user_id: user_id
            },
            success: function(response) {
                if (response.success) {
                    row.fadeOut(200, function() {
                        $(this).remove();

                        // Check if table is now empty
                        var table = $('table.md-pending-verifications');
                        if (table.find('tbody tr').length === 0) {
                            table.find('tbody').html('<tr><td colspan="6">' + mdAdmin.strings.noPendingVerifications + '</td></tr>');
                        }
                    });
                } else {
                    alert(response.data);
                }
            },
            error: function() {
                alert(mdAdmin.strings.errorCancelling);
            },
            complete: function() {
                button.prop('disabled', false).text(mdAdmin.strings.cancel);
            }
        });
    });

    // Cancel all pending verifications
    $(document).on('click', '#md-cancel-all-pending', function() {
        var button = $(this);

        if (!confirm(mdAdmin.strings.confirmCancelAllVerifications)) {
            return;
        }

        button.prop('disabled', true).text(mdAdmin.strings.cancellingAll);

        $.ajax({
            url: mdAdmin.ajaxUrl,
            type: 'POST',
            data: {
                action: 'md_cancel_all_pending_verifications',
                nonce: mdAdmin.nonce
            },
            success: function(response) {
                if (response.success) {
                    alert(response.data.message);
                    $('table.md-pending-verifications tbody').html('<tr><td colspan="6">' + mdAdmin.strings.noPendingVerifications + '</td></tr>');
                } else {
                    alert(response.data);
                }
            },
            error: function() {
                alert(mdAdmin.strings.errorCancellingAll);
            },
            complete: function() {
                button.prop('disabled', false).text(mdAdmin.strings.cancelAll);
            }
        });
    });

})(jQuery);
