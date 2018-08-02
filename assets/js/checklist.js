(function($, bc_security_checklist) {
    $(function() {
        var $checks = $('.bcs-check');
        var $run_checks_buttons = $('button.bcs-run-checks');

        // Activate "select all" button.
        $('#bcs-mark-all-checks').prop('disabled', false).on('click', function() {
            $checks.find('input[type="checkbox"]').prop('checked', true);
        });

        // Activate "select none" button.
        $('#bcs-mark-no-checks').prop('disabled', false).on('click', function() {
            $checks.find('input[type="checkbox"]').prop('checked', false);
        });

        // Activate "select only passing" button.
        var $select_passing_checks_button = $('#bcs-mark-passing-checks').on('click', function() {
            $checks.find('input[type="checkbox"]').prop('checked', function() { return $(this).closest('.bcs-check').hasClass('bcs-check--ok'); });
        });

        // Activate "Run checks" buttons.
        $run_checks_buttons.on('click', function() {
            // Disable all "Run checks" buttons.
            $run_checks_buttons.prop('disabled', true);

            var $button = $(this);
            var requests = [];

            $checks.filter('.' + $button.data('check-class')).each(function() {
                var $check = $(this).removeClass('bcs-check--ok').removeClass('bcs-check--ko').addClass('bcs-check--running');
                var $message = $('.bcs-check__message', $check).html(bc_security_checklist.messages.check_is_running);

                $check.closest('table').removeClass('bcs-checklist--initial');

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
                        if (response.success && response.data.status !== null) {
                            $check.addClass('bcs-check--' + (response.data.status ? 'ok' : 'ko'));
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
                $select_passing_checks_button.prop('disabled', false);
            });
        });
    });
})(jQuery, bc_security_checklist);
