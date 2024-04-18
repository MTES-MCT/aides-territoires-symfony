require('../../form/trumbowyg.js');

$(function() {
    // Gestionnaire d'événement 'submit'
    $(document).on('submit', 'form[name="form"]', function(e) {
        e.preventDefault();
        $("#btn_modal_waiting").attr("data-fr-opened", "true");
        setTimeout(function() {
            $(e.target).off('submit');
            e.target.submit();
        }, 250);
    }); 
    
}); 
