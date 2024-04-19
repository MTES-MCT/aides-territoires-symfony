$(function() {
    $(document).on({
        change: function (e) {
            toggleIntercommunalityField(this);
        }
    }, '#register_organizationType');

    toggleIntercommunalityField('#register_organizationType');

    $(document).on({
        change: function (e) {
            toggleIntercommunalityField(this);
        }
    }, '#organization_edit_organizationType');

    toggleIntercommunalityField('#organization_edit_organizationType');
})

function toggleIntercommunalityField(fieldToggler)
{
    if ($(fieldToggler).length) {
        if ($(fieldToggler).val() == 2) {
            $('#intercommunality-type-field-collapse').addClass('fr-collapse--expanded');
        } else {
            $('#intercommunality-type-field-collapse').removeClass('fr-collapse--expanded');
        }
    }
}