    /**
     * Enable the acquisition_channel_comment field toggling.
     */
    function toggleAcquisitionChannelCommentField(form, div) {

        let select = form.find('#register_acquisitionChannel option[value="other"]');
        let selected = select.prop('selected');
        let hasErrors = div.find('p.error').length > 0;

        if (selected || hasErrors) {
            div.addClass('fr-collapse--expanded');
        } else {
            div.removeClass('fr-collapse--expanded');
        }
    };

$(document).ready(function () {
    let allowToggle = true;
    // If a "source" URL parameter is set, set its value in the acquisition_channel_comment field,
    // And set the acquisition_channel value to "animator"
    let searchParams = new URLSearchParams(window.location.search);
    if (searchParams.has('source')) {
        let source = searchParams.get('source');
        $("#register_acquisitionChannel").val("animator");
        $("#register_acquisitionChannelComment").val(source);
        allowToggle = false;
    }

    if (allowToggle) {
        // Only display acquisition_channel related field when the `other`
        // option is selected.
        let registerForm = $('form[name="register"]');
        let AcquisitionChannelCommentFieldDiv = $('#acquisition-channel-comment-collapse');

        toggleAcquisitionChannelCommentField(registerForm, AcquisitionChannelCommentFieldDiv);

        registerForm.on('change', function () {
            toggleAcquisitionChannelCommentField(registerForm, AcquisitionChannelCommentFieldDiv);
        });
    }

});
