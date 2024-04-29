$(function(t) {
    $(document).on({
        submit: function(e) {
            n = document.querySelectorAll('input[type="checkbox"].form-batch-checkbox:checked');
            n.forEach((function(e, i) {
                $('<input>', {
                    type: 'hidden',
                    name: 'batchActionEntityIds['+i+']',
                    value: e.value
                }).appendTo('#form-project-reference-association');
            }));
        }
    }, '#form-project-reference-association');

    $(document).on({
        submit: function(e) {
            n = document.querySelectorAll('input[type="checkbox"].form-batch-checkbox:checked');
            n.forEach((function(e, i) {
                $('<input>', {
                    type: 'hidden',
                    name: 'batchActionEntityIds['+i+']',
                    value: e.value
                }).appendTo('#form-keyword-reference-association');
            }));
        }
    }, '#form-keyword-reference-association');
});