(function($, bc_security_checklist) {
    $(function() {
        var $checklist = $('#bcs-checklist');
        var $checks = $checklist.find('.bcs-check');

        // Activate "select all passing checks" button.
        $('#bcs-mark-passing-checks').prop('disabled', false).on('click', function() {
            $checks.find('input[type="checkbox"]').each(function() {
               $(this).prop('checked', $(this).closest('.bcs-check').hasClass('bcs-check--ok'));
            });
        });

        // Run all checks that need to be executed asynchronously.
        $('#bcs-run-checks').on('click', function() {
            $checklist.removeClass('bcs-checklist--initial');
            // Disable button while checks are running.
            var $button = $(this).prop('disabled', true);
            var requests = [];

            $checks.each(function() {
                var $check = $(this).removeClass('bcs-check--init').addClass('bcs-check--running');
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
                        $message.html(bc_security_checklist.message.check_failed);
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

            // Re-enable button when all checks are processed.
            $.when.apply($, requests).always(function() { $button.prop('disabled', false); });
        });
    });
})(jQuery, bc_security_checklist);
