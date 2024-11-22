// Only requires exit confirmation if something was changed.
function enableExitConfirmation(aidEditForm) {
    const initialData = aidEditForm.serialize();
    let eventAttached = false;

    function checkForChanges() {
        if (eventAttached) return;

        const newData = aidEditForm.serialize();
        const changed = initialData !== newData;

        if (changed) {
            // Attach the "beforeunload" event if something changed
            $(window).on('beforeunload', handleBeforeUnload);
            eventAttached = true;
        }
    }

    function handleBeforeUnload() {
        return "Êtes-vous certain de vouloir quitter cette page ? Vos modifications seront perdues.";
    }

    // Listen for changes in the form
    aidEditForm.on('change', checkForChanges);

    // Listen for changes in Trumbowyg editors
    aidEditForm.find('.trumbowyg').on('tbwchange tbwblur', checkForChanges);
}

// Unbind the exit confirmation
function disableExitConfirmation() {
    $(window).off('beforeunload');
}

$(function(){
    const aidEditForm = $('form.form-aid');

    if (aidEditForm.length) {
        enableExitConfirmation(aidEditForm);

        // Don't ask for confirmation if the form was submitted
        aidEditForm.on('submit', disableExitConfirmation);
    }
});
