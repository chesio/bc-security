(function($, bc_security_checklist) {
    $(function() {
        var $checks = $('.bcs-check');
        // Grab all "Rerun" check buttons + "Run basic/advanced checks" buttons
        var $run_checks_buttons = $('button.bcs-run-check, button.bcs-run-checks');

        // Activate "select all" button.
        $('#bcs-mark-all-checks').prop('disabled', false).on('click', function() {
            $checks.find('input[type="checkbox"]').prop('checked', true);
        });

        // Activate "select none" button.
        $('#bcs-mark-no-checks').prop('disabled', false).on('click', function() {
            $checks.find('input[type="checkbox"]').prop('checked', false);
        });

        // Activate "select only passing" button.
        $('#bcs-mark-passing-checks').prop('disabled', false).on('click', function() {
            $checks.find('input[type="checkbox"]').prop('checked', function() { return $(this).closest('.bcs-check').hasClass('bcs-check--ok'); });
        });

        // Activate "Run checks" buttons.
        $run_checks_buttons.on('click', function() {
            // Disable all "Run checks" buttons.
            $run_checks_buttons.prop('disabled', true);

            var $button = $(this);
            var requests = [];
            // Checks to be run are defined either by class or by ID.
            var $selector
                = ($button.data('check-class') ? '.' . $button.data('check-class') : '')
                + ($button.data('check-id') ? '#' + $button.data('check-id') : '')
            ;

            $checks.filter($selector).each(function() {
                var $check = $(this).removeClass('bcs-check--ok').removeClass('bcs-check--ko').addClass('bcs-check--running');
                var $last_run = $('.bcs-check__last-run', $check);
                var $message = $('.bcs-check__message', $check).html(bc_security_checklist.messages.check_is_running);

                // https://api.jquery.com/jQuery.ajax/
                var request = $.ajax({
                    url     : bc_security_checklist.ajaxurl,
                    method  : 'POST',
                    data    : {action: bc_security_checklist.action, _ajax_nonce: bc_security_checklist.nonce, check_id: $check.data('check-id')},
                    dataType: 'json',
                    cache   : false,
                    timeout : 0, // no timeout
                    error   : function() {
                        $message.html(bc_security_checklist.messages.check_failed);
                    },
                    success  : function(response) {
                        if (response.success) {
                            $last_run.text(response.data.timestamp);
                            if (response.data.status !== null) {
                                $check.addClass('bcs-check--' + (response.data.status ? 'ok' : 'ko'));
                            }
                        }
                        $message.html(response.data.message);
                    },
                    complete : function() {
                        $check.removeClass('bcs-check--running').addClass('bcs-check--done');
                    }
                });

                requests.push(request);
            });

            $.when.apply($, requests).always(function() {
                $run_checks_buttons.prop('disabled', false);
            });
        });
    });
})(jQuery, bc_security_checklist);
