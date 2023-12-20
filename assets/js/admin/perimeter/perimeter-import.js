$(function(){
    $(document).on({
        click: function (e) {
            $('#timer-tracking').append('<p>Script commencé : ' + new Date().toLocaleString() + '</p>');
            $(this).attr('disabled', 'disabled');
            importItem();
        }
    }, '#start-import');
});

function importItem()
{
    $.ajax({
        url: url,
        type: 'POST',
        dataType: 'json',
        data: {
            'start': start,
            'max': max,
            'idPerimeterImport': idPerimeterImport,
            'cityCode': cityCodes[current],
            'current': current
        },
        success: function(data) {
            if (data.status == 'success') {
                current = data.current;

                updateProgress(getPercentage());
                if (data.notFound) {
                    for (var i=0; i<data.notFound.length; i++) {
                        $('#not-found-list').append('<li>' + data.notFound[i] + '</li>');
                    }
                    if (data.notFound.length > 0) {
                        $('.progress-bar').addClass('bg-warning');
                    }
                }
                if (current < max) {
                    importItem();
                } else {
                    $('#timer-tracking').append('<p>Script terminé : ' + new Date().toLocaleString() + '</p>');
                    if ($('#not-found-list').html() !== '') {
                        $('.progress-bar').addClass('bg-success');
                    }
                }
            } else {
                alert('Error: ' + data.message);
                $('.progress-bar').addClass('bg-danger');
            }
        },
        error: function(data) {
            alert('Error: ' + data.message);
            $('.progress-bar').addClass('bg-danger');
        }
    });

}
function getPercentage() {
    return (current / max) * 100;
}

function updateProgress(percent) {
    if (percent > 100) {
        percent = 100;
    }
    var percentVal = Math.ceil(percent) + '%';
    $('.progress-bar').width(percentVal);
    $('.progress-bar').html(percentVal);
}