    /**
     * Champs avec un maxlength
     */
    $('*[maxlength]').each(function(){
        var thisElt = $(this);
        var counter = thisElt.parents('.fr-input-group').find('.input-length-counter')
        var text_to_check = thisElt.val().replace(/(\r\n|\n|\r)/gm,"");
        $('.current-count', counter).text(text_to_check.length);

        $(document).on({
            keyup: function(){
                var text_to_check = thisElt.val().replace(/(\r\n|\n|\r)/gm,"");
                $('.current-count', counter).text(text_to_check.length);
            }
        },this);
    });