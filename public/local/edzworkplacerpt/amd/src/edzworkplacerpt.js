define(['jquery', 'core/modal_factory', 'core/ajax'], function($, ModalFactory, Ajax) {
    return {
        init: function() {
            $(document).ready(function() {
                function updateSendBtn() {
                    var checked = $('.edz-row-checkbox:checked').length;
                    if (checked > 0) {
                        $('#edz_send_message_btn').removeAttr('disabled');
                    } else {
                        $('#edz_send_message_btn').attr('disabled', 'disabled');
                    }
                }

                $(document).on('change', '#edz_select_all', function() {
                    $('.edz-row-checkbox').prop('checked', $(this).prop('checked'));
                    updateSendBtn();
                });

                $(document).on('change', '.edz-row-checkbox', function() {
                    updateSendBtn();
                });

                $('#edz_send_message_btn').on('click', function() {
                    var selected = [];
                    $('.edz-row-checkbox:checked').each(function() { selected.push(parseInt($(this).val())); });
                    if (selected.length === 0) {
                        alert('Please select at least one user.');
                        return;
                    }

                    ModalFactory.create({type: 'modal', title: 'Send message'}).done(function(modal) {
                        var body = '<form id=\"edz_message_form\">' +
                                   '<div class=\"form-group\"><label>Subject</label><input type=\"text\" id=\"edz_m_subject\" name=\"subject\" class=\"form-control\" /></div>' +
                                   '<div class=\"form-group\"><label>Message (HTML allowed)</label><textarea id=\"edz_m_body\" name=\"message\" class=\"form-control\" rows=\"10\"></textarea></div>' +
                                   '<div class=\"modal-footer\"><button type=\"button\" class=\"btn btn-secondary\" id=\"edz_cancel\">Cancel</button>' +
                                   '<button type=\"button\" class=\"btn btn-primary\" id=\"edz_send\">Send rashid</button></div></form>';
                        modal.setBody(body);
                        modal.show();

                        $('#edz_cancel').on('click', function() { modal.hide(); });

                        $('#edz_send').on('click', function() {
                            var subj = $('#edz_m_subject').val();
                            var msg = $('#edz_m_body').val();
                            if (!subj || !msg) { alert('Please fill subject and message'); return; }

                            var call = {
                                methodname: 'local_edzworkplacerpt_send_message',
                                args: { touserids: selected, subject: subj, messagehtml: msg }
                            };

                            Ajax.call([call])[0].done(function(response) {
                                if (response.sent) {
                                    alert('Messages sent: ' + response.sent);
                                } else {
                                    alert('No messages sent');
                                }
                                modal.hide();
                            }).fail(function(err) {
                                alert('Failed to send: ' + JSON.stringify(err));
                            });
                        });
                    });
                });

            });
        }
    };
});
