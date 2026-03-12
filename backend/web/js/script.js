$('.remove-image').on('click', function (e) {
    e.preventDefault();
    let deleteUrl = $(this).attr('href');
    $.post(deleteUrl, {}, function (response) {

        if (response) {
            $(this).closest('tr').remove();
        } else {
            alert('Cannot delete this file');
        }
    });
});