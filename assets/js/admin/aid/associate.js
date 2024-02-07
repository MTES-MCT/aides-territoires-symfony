$(function(t) {
    $(document).on({
        submit: function(e) {
            n = document.querySelectorAll('input[type="checkbox"].form-batch-checkbox:checked');
            n.forEach((function(e, i) {
                $('#form-project-reference-association').append('<input type="hidden" name="batchActionEntityIds['+i+']" value="'+e.value+'">');
            }));
        }
    }, '#form-project-reference-association');

    $(document).on({
        submit: function(e) {
            n = document.querySelectorAll('input[type="checkbox"].form-batch-checkbox:checked');
            n.forEach((function(e, i) {
                $('#form-keyword-reference-association').append('<input type="hidden" name="batchActionEntityIds['+i+']" value="'+e.value+'">');
            }));
        }
    }, '#form-keyword-reference-association');
});