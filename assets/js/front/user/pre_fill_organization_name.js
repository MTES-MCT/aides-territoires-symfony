    /**
     * Pre-fill the organization_name value depending on certain variables
     * and hide it in some case
     */
    function preFillOrganizationName(form) {

        let organizationType = parseInt(form.find("#register_organizationType option:selected").val());

        let organizationNameField = $("#register_organizationName");

        let perimeterNameValue = '';
        if ($("#register_perimeter_autocomplete").find('option:selected').val() !== '') {
            perimeterNameValue = $("#register_perimeter_autocomplete").find('option:selected').text().split(' (')[0];
        }
        

        if (organizationType == 10) {
            let full_name = "";
            // Set organization name to user name for private persons
            if (form.attr('name') == "register") {
                let first_name = form.find("#register_firstname").val();
                let last_name = form.find("#register_lastname").val();
                full_name = first_name + " " + last_name;
            } else {
                full_name = $('.at-username').first().text()
            }

            organizationNameField.val(full_name);
        } else if ([1, 3, 4, 2].includes(organizationType)) {
            // Set organization name relative to perimeter name for collectivities
            let organizationName = perimeterNameValue;

            if (organizationType == 1) {
                organizationName = "Mairie de " + perimeterNameValue
            } else if (organizationType == 4) {
                organizationName = "Région " + perimeterNameValue
            }

            if (perimeterNameValue) {
                organizationNameField.val(organizationName);
            } else {
                organizationNameField.val("");
            }
        }
    }


$(document).ready(function () {
    /* Only call the preFillOrganizationName() function if
     - the name field is empty
     - one of the fields used to determine it is changed
    */
    let organizationForm = $('form[name="register"], #create-organization-form, #update-organization-form');
    let organizationNameField = $("#register_organizationName");

    if (organizationNameField == "") {
        preFillOrganizationName(organizationForm);
    }

    $(document).on({
        change: function (e) {
            preFillOrganizationName(organizationForm);
        }
    }, '#register_organizationType, #register_perimeter_autocomplete, #register_intercommunalityType');

    // organizationForm.find(":input").on('change', function () {
    //     let fieldID = $(this).attr('id');

    //     if (["#register_organizationType", "#register_perimeter_autocomplete", "#register_intercommunalityType"].includes(fieldID)) {
    //         preFillOrganizationName(organizationForm);
    //     }

    // });
});
