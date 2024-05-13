$(function(t) {
    $(document).on({
        submit: function(e) {
            n = document.querySelectorAll('input[type="checkbox"].form-batch-checkbox:checked');
            n.forEach((function(e, i) {
                $('<input>', {
                    type: 'hidden',
                    name: 'batchActionEntityIds['+i+']',
                    value: e.value
                }).appendTo('#form-backer-association');
            }));
        }
    }, '#form-backer-association');
});