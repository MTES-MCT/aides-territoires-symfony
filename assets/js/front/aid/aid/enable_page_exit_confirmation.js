// Only requires exit confirmation if something was changed.
function enableExitConfirmation (aidEditForm) {
    var initialData = aidEditForm.serialize();
    var eventAttached = false;

    aidEditForm.on('change', function () {

        // Don't bother if the event was already bound
        if (eventAttached) return;

        // Was some form data actually changed?
        var newData = aidEditForm.serialize();
        var changed = initialData != newData;
        if (changed) {

            // If so, bind the "onbeforeunload" event
            $(window).bind('beforeunload', function () {
                return "Êtes-vous certain de vouloir quitter cette page ? Vos modifications seront perdues.";
            });
        }
    });
};

// Unbind the exit confirmation
function disableExitConfirmation() {
    $(window).unbind('beforeunload');
};

$(document).ready(function () {
    // Prevent status update when edit form was modified
    // to prevent data loss.
    var aidEditForm = $('form.form-aid');
    enableExitConfirmation(aidEditForm);

    // Don't ask for a confirmation if the form was submitted
    aidEditForm.on('submit', disableExitConfirmation);
});