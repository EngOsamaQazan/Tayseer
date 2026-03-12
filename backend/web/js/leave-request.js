$('#leaverequest-end_at').on('change', function () {
    if ($('#leaverequest-start_at').val() != '') {
        can_get_leave_days();

    }
});
$('#leaverequest-start_at').on('change', function () {
    if ($('#leaverequest-end_at').val() != '') {
        can_get_leave_days();

    }
});
function can_get_leave_days() {
    total = ($('#days_lift').html() - (Date.parse($('#leaverequest-end_at').val()) - Date.parse($('#leaverequest-start_at').val())) / (1000 * 3600 * 24) + 1);

    if (total > 0) {
        $('.btn-success').prop('disabled', false).css('background-color', '#008d4c');
        $('#leave_days_message').html(leave_messages[0]);
        $('#leave_days_cridet').html(total + '');
    } else {
         $('.btn-success').prop('disabled', true).css('background-color', '#dedede');
        $('#leave_days_message').html(leave_messages[1]);
        $('#leave_days_cridet').html();
    }
}

function form_elemnt_enable(status) {
    if (status == false) {
        $('.btn-success').prop('disabled', true).css('background-color', '#dedede');
        $('#leaverequest-start_at').prop('disabled', true);
        $('#leaverequest-end_at').prop('disabled', true);
        $('#leaverequest-reason').prop('disabled', true);
        $('.field-leaverequest-attachment').prop('disabled', true);
        $('.available_days').css('color', 'red');
    } else {
        $('.btn-success').prop('disabled', false).css('background-color', '#008d4c');
        $('#leaverequest-start_at').prop('disabled', false);
        $('#leaverequest-end_at').prop('disabled', false);
        $('#leaverequest-reason').prop('disabled', false);
        $('.field-leaverequest-attachment').prop('disabled', false);
        $('.available_days').css('color', '#1ac11a');
    }

}