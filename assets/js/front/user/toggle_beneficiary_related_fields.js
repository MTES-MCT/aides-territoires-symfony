$(function() {
    $(document).on({
        change: function (e) {
            toggleIntercommunalityField(this);
        }
    }, '#register_organizationType');

    toggleIntercommunalityField('#register_organizationType');
})

function toggleIntercommunalityField(fieldToggler)
{
    if ($(fieldToggler).val() == 1) {
        $('#beneficiary-fields-collapse').addClass('fr-collapse--expanded');
    } else {
        $('#beneficiary-fields-collapse').removeClass('fr-collapse--expanded');
    }
}