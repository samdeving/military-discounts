/**
 * Military Discounts - Public JavaScript
 */

(function ($) {
    'use strict';

    var currentStep = 1;
    var verificationType = '';
    var formFields = [];
    var militaryEmail = '';

    // Initialize
    $(document).ready(function () {
        initStepNavigation();
    });

    // Step navigation
    function initStepNavigation() {
        // Type selection
        $(document).on('change', 'input[name="verification_type"]', function () {
            verificationType = $(this).val();
            $('.md-next-step').prop('disabled', false);
        });

        // Next step
        $(document).on('click', '.md-next-step', function () {
            if ($(this).prop('disabled')) return;

            if (currentStep === 1) {
                if (!verificationType) {
                    showMessage('error', mdPublic.strings.selectType);
                    return;
                }
                loadStep2Fields();
            } else if (currentStep === 2) {
                if (!validateStep2()) return;
                loadStep3();
            }
        });

        // Previous step
        $(document).on('click', '.md-prev-step', function () {
            goToStep(currentStep - 1);
        });
    }

    // Go to specific step
    function goToStep(step) {
        currentStep = step;

        // Update step indicators
        $('.md-step').removeClass('active completed');
        for (var i = 1; i <= 3; i++) {
            if (i < step) {
                $('.md-step[data-step="' + i + '"]').addClass('completed');
            } else if (i === step) {
                $('.md-step[data-step="' + i + '"]').addClass('active');
            }
        }

        // Show step content
        $('.md-form-step').removeClass('active');
        $('.md-form-step[data-step="' + step + '"]').addClass('active');

        hideMessage();
    }

    // Load step 2 fields via AJAX
    function loadStep2Fields() {
        var $fields = $('#md-dynamic-fields');
        $fields.html('<p style="text-align:center;">' + mdPublic.strings.loading + '</p>');

        $.ajax({
            url: mdPublic.ajaxUrl,
            type: 'POST',
            data: {
                action: 'md_get_form_fields',
                nonce: mdPublic.nonce,
                type: verificationType
            },
            success: function (response) {
                if (response.success) {
                    $fields.html(response.data.html);
                    formFields = response.data.fields;

                    // Update title
                    var title = verificationType === 'veteran'
                        ? 'Enter Your Information'
                        : 'Enter Your Military Email';
                    $('.md-form-step[data-step="2"] .md-step-title').text(title);

                    goToStep(2);
                } else {
                    showMessage('error', response.data);
                }
            },
            error: function () {
                showMessage('error', mdPublic.strings.errorOccurred);
            }
        });
    }

    // Validate step 2 fields
    function validateStep2() {
        var valid = true;

        $('.md-form-field input, .md-form-field select').each(function () {
            var $field = $(this);
            var $parent = $field.closest('.md-form-field');

            $field.removeClass('error');
            $parent.find('.field-error').remove();

            if ($field.prop('required') && !$field.val().trim()) {
                valid = false;
                $field.addClass('error');
                $parent.append('<span class="field-error">' + mdPublic.strings.requiredField + '</span>');
            }

            if ($field.attr('type') === 'email' && $field.val()) {
                if (!isValidEmail($field.val())) {
                    valid = false;
                    $field.addClass('error');
                    $parent.append('<span class="field-error">' + mdPublic.strings.invalidEmail + '</span>');
                }
            }
        });

        // Check military email validity
        if (valid && verificationType === 'military') {
            var email = $('#md-militaryEmail').val().trim();
            $.ajax({
                url: mdPublic.ajaxUrl,
                type: 'POST',
                async: false, // Make synchronous call to block form submission
                data: {
                    action: 'md_validate_military_email',
                    nonce: mdPublic.nonce,
                    email: email
                },
                success: function (response) {
                    if (!response.success) {
                        valid = false;
                        showMessage('error', response.data);
                    }
                },
                error: function () {
                    valid = false;
                    showMessage('error', mdPublic.strings.errorOccurred);
                }
            });
        }

        // Check billing name and email-to-name match for military verification before proceeding to step 3
        if (valid && verificationType === 'military') {
            // Check billing name
            $.ajax({
                url: mdPublic.ajaxUrl,
                type: 'POST',
                async: false, // Make synchronous call to block form submission
                data: {
                    action: 'md_check_billing_name',
                    nonce: mdPublic.nonce
                },
                success: function (response) {
                    if (!response.success) {
                        valid = false;
                        showMessage('error', response.data);
                    }
                },
                error: function () {
                    valid = false;
                    showMessage('error', mdPublic.strings.errorOccurred);
                }
            });

            // Check email-to-name match if billing name is valid
            if (valid) {
                var email = $('#md-militaryEmail').val().trim();
                $.ajax({
                    url: mdPublic.ajaxUrl,
                    type: 'POST',
                    async: false, // Make synchronous call to block form submission
                    data: {
                        action: 'md_check_email_name_match',
                        nonce: mdPublic.nonce,
                        email: email
                    },
                    success: function (response) {
                        if (!response.success) {
                            valid = false;
                            showMessage('error', response.data);
                        }
                    },
                    error: function () {
                        valid = false;
                        showMessage('error', mdPublic.strings.errorOccurred);
                    }
                });
            }
        }

        return valid;
    }

    // Load step 3 content
    function loadStep3() {
        var $content = $('#md-step-3-content');

        if (verificationType === 'veteran') {
            loadVeteranConfirmation($content);
        } else {
            loadMilitaryOTP($content);
        }
    }

    // Load veteran confirmation step
    function loadVeteranConfirmation($content) {
        var formData = getFormData();

        var html = '<div class="md-confirmation">';
        html += '<h3>Confirm Your Information</h3>';
        html += '<p>Please review your information before submitting.</p>';
        html += '<div class="md-confirmation-summary">';
        html += '<h4>Verification Details</h4>';
        html += '<dl>';

        for (var key in formData) {
            if (formData.hasOwnProperty(key) && formData[key]) {
                var label = getFieldLabel(key);
                html += '<dt>' + label + '</dt>';
                html += '<dd>' + escapeHtml(formData[key]) + '</dd>';
            }
        }

        html += '</dl>';
        html += '</div>';
         html += '<div class="md-form-actions">';
         html += '<button type="button" class="button wp-element-button md-prev-step">' + mdPublic.strings.back + '</button>';
         html += '<button type="button" class="button button-primary wp-element-button md-submit-veteran">' + mdPublic.strings.submit + '</button>';
         html += '</div>';
        html += '</div>';

        $content.html(html);
        goToStep(3);
    }

    // Load military OTP step
    function loadMilitaryOTP($content) {
        militaryEmail = $('#md-militaryEmail').val();

        var html = '<div class="md-otp-section">';
        html += '<h3>Verify Your Military Email</h3>';
        html += '<p>We\'ll send a verification code to:</p>';
        html += '<p><strong>' + escapeHtml(militaryEmail) + '</strong></p>';
         html += '<div class="md-otp-send">';
         html += '<button type="button" class="button button-primary wp-element-button md-send-otp">' + mdPublic.strings.submit + '</button>';
         html += '</div>';
         html += '<div class="md-otp-verify" style="display:none;">';
         html += '<div class="md-otp-sent-message">' + mdPublic.strings.otpSent + '</div>';
         html += '<div class="md-otp-input-wrapper">';
          html += '<input type="text" class="input-text md-otp-input" maxlength="6" placeholder="000000" pattern="[0-9]*" inputmode="numeric">';
         html += '</div>';
         html += '<button type="button" class="button button-primary wp-element-button md-verify-otp">' + mdPublic.strings.verifyCode + '</button>';
         html += '<p class="md-resend-link"><a href="#" class="md-resend-otp">Resend code</a></p>';
         html += '</div>';
         html += '<div class="md-form-actions">';
         html += '<button type="button" class="button wp-element-button md-prev-step">' + mdPublic.strings.back + '</button>';
        html += '</div>';
        html += '</div>';

        $content.html(html);
        goToStep(3);
    }

    // Send OTP
    $(document).on('click', '.md-send-otp, .md-resend-otp', function (e) {
        e.preventDefault();

        var $btn = $(this);
        var isResend = $btn.hasClass('md-resend-otp');
        $btn.prop('disabled', true);

        if ($btn.is('button')) {
            $btn.text(mdPublic.strings.sendingOtp);
        }

        $.ajax({
            url: mdPublic.ajaxUrl,
            type: 'POST',
            data: {
                action: 'md_send_military_otp',
                nonce: mdPublic.nonce,
                email: militaryEmail
            },
            success: function (response) {
                if (response.success) {
                    $('.md-otp-send').hide();
                    $('.md-otp-verify').show();
                    $('.md-otp-input').focus();
                    hideMessage(); // Hide any existing messages
                    
                    // Update the OTP sent message based on whether it's a resend
                    if (isResend) {
                        $('.md-otp-sent-message').text(mdPublic.strings.otpResent);
                    } else {
                        $('.md-otp-sent-message').text(mdPublic.strings.otpSent);
                    }

                } else {
                    showMessage('error', response.data);
                }
            },
            error: function () {
                showMessage('error', mdPublic.strings.errorOccurred);
            },
            complete: function () {
                $btn.prop('disabled', false);
                if ($btn.is('button')) {
                    $btn.text(mdPublic.strings.submit);
                }
            }
        });
    });

    // Verify OTP
    $(document).on('click', '.md-verify-otp', function () {
        var $btn = $(this);
        var otp = $('.md-otp-input').val().trim();

        if (!otp || otp.length !== 6) {
            showMessage('error', 'Please enter a 6-digit code.');
            return;
        }

        $btn.prop('disabled', true).text(mdPublic.strings.verifyingOtp);

        $.ajax({
            url: mdPublic.ajaxUrl,
            type: 'POST',
            data: {
                action: 'md_verify_military_otp',
                nonce: mdPublic.nonce,
                otp: otp
            },
            success: function (response) {
                if (response.success) {
                    var redirectUrl = response.data.redirect_url || '';
                    var redirectDelay = response.data.redirect_delay || 2000;
                    showSuccessAndReload(response.data.message, redirectUrl, redirectDelay);
                } else {
                    var message = response.data.message || response.data;
                    
                    // If we have failed attempts data, update the UI
                    if (response.data.failed_attempts) {
                        updateFailedAttemptsUI('military', response.data.failed_attempts);
                    }
                    
                    showMessage('error', message);
                    $btn.prop('disabled', false).text(mdPublic.strings.verifyCode);
                }
            },
            error: function () {
                showMessage('error', mdPublic.strings.errorOccurred);
                $btn.prop('disabled', false).text(mdPublic.strings.verifyCode);
            }
        });
    });

    // Submit veteran verification
    $(document).on('click', '.md-submit-veteran', function () {
        var $btn = $(this);
        var formData = getFormData();

        $btn.prop('disabled', true).text(mdPublic.strings.submitting);

        $.ajax({
            url: mdPublic.ajaxUrl,
            type: 'POST',
            data: {
                action: 'md_submit_veteran_verification',
                nonce: mdPublic.nonce,
                formData: formData
            },
            success: function (response) {
                if (response.success) {
                    if (response.data.status === 'approved') {
                        var redirectUrl = response.data.redirect_url || '';
                        var redirectDelay = response.data.redirect_delay || 2000;
                        showSuccessAndReload(response.data.message, redirectUrl, redirectDelay);
                    } else if (response.data.status === 'queued') {
                        showSuccessAndReload(response.data.message);
                    }
                } else {
                    var message = response.data.message || response.data;
                    
                    // If we have failed attempts data, update the UI
                    if (response.data.failed_attempts) {
                        updateFailedAttemptsUI('veteran', response.data.failed_attempts);
                    }
                    
                    showMessage('error', message);
                    $btn.prop('disabled', false).text(mdPublic.strings.submit);
                }
            },
            error: function () {
                showMessage('error', mdPublic.strings.errorOccurred);
                $btn.prop('disabled', false).text(mdPublic.strings.submit);
            }
        });
    });

    // Helper functions
    function getFormData() {
        var data = {};
        $('.md-form-fields input, .md-form-fields select').each(function () {
            var name = $(this).attr('name');
            if (name) {
                data[name] = $(this).val();
            }
        });
        return data;
    }

    function getFieldLabel(key) {
        for (var i = 0; i < formFields.length; i++) {
            if (formFields[i].id === key || formFields[i].api_field === key) {
                return formFields[i].label;
            }
        }
        return key;
    }

    function showMessage(type, message) {
        var $msg = $('#md-messages');
        $msg.removeClass('success error info').addClass(type).html(message).show();

        $('html, body').animate({
            scrollTop: $msg.offset().top - 100
        }, 300);
    }

    function hideMessage() {
        $('#md-messages').hide();
    }

    function showSuccessAndReload(message, redirectUrl, redirectDelay) {
        var html = '<div class="md-status md-status-verified">';
        html += '<div class="md-status-icon"><span class="dashicons dashicons-yes-alt"></span></div>';
        html += '<div class="md-status-content"><h3>' + message + '</h3>';
        
        if (redirectUrl) {
            html += '<p>Redirecting...</p></div></div>';
        } else {
            html += '<p>Refreshing page...</p></div></div>';
        }

        $('.md-form-wrapper').html(html);

        // Use provided delay or default to 2000ms
        var delay = redirectDelay > 0 ? redirectDelay : 2000;

        setTimeout(function () {
            if (redirectUrl) {
                window.location.href = redirectUrl;
            } else {
                location.reload();
            }
        }, delay);
    }

    function escapeHtml(text) {
        var div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    function isValidEmail(email) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    }

    // Update failed attempts UI
    function updateFailedAttemptsUI(type, failedAttempts) {
        var $attemptsContainer = $('.md-failed-attempts');
        var selector = type === 'veteran' ? '.md-failed-veteran' : '.md-failed-military';
        var $attemptsElement = $attemptsContainer.find(selector);
        
        // If the attempts element doesn't exist, create it
        if ($attemptsElement.length === 0) {
            var typeText = type === 'veteran' ? 'Veteran' : 'Military';
            var html = '<p class="md-failed-' + type + '">' + 
                typeText + ' verification: ' + failedAttempts.count + '/' + failedAttempts.max + ' failed attempts' + 
                '<span class="md-attempts-bar">' +
                '<span class="md-attempts-progress-fill" style="width: ' + failedAttempts.progress + '%;"></span>' +
                '</span>' +
                '</p>';
            $attemptsContainer.append(html);
            $attemptsElement = $attemptsContainer.find(selector);
        } else {
            // Update existing element
            var typeText = type === 'veteran' ? 'Veteran' : 'Military';
            var newText = typeText + ' verification: ' + failedAttempts.count + '/' + failedAttempts.max + ' failed attempts';
            $attemptsElement.contents().first().replaceWith(newText);
            
            // Update progress bar
            var $progressBar = $attemptsElement.find('.md-attempts-progress-fill');
            $progressBar.css('width', failedAttempts.progress + '%');
            
            // Update progress bar class based on progress
            $progressBar.removeClass('warning danger');
            if (failedAttempts.progress >= 80) {
                $progressBar.addClass('danger');
            } else if (failedAttempts.progress >= 50) {
                $progressBar.addClass('warning');
            }
        }
        
        // Ensure the container is visible
        $attemptsContainer.show();
    }

})(jQuery);
