(function($){
    $.entity_checkbox_absolute_type = function(el, options){
        // To avoid scope issues, use 'base' instead of 'this'
        // to reference this class from internal events and functions.
        var base = this;
        
        // Access to jQuery and DOM versions of element
        base.$el = $(el);
        base.el = el;
        
        // Add a reverse reference to the DOM object
        base.$el.data("entity_checkbox_absolute_type", base);
        
        base.init = function(){
            
            base.opener = base.$el.find('.opener');
            base.filter = base.$el.find('.filter');
            base.checboxesWrapper = base.$el.find('.checkboxes-wrapper');
            base.options = $.extend({},$.entity_checkbox_absolute_type.defaultOptions, options);
            base.placeholder = $(base.opener).text();
            base.setResumeText();
            // Put your initialization code here
            // placement de la liste en absolute
            // base.setAutocompletePositions();

            // les ecouteurs
            base.addListeners();
        };
        
        // Sample Function, Uncomment to use
        // base.functionName = function(paramaters){
        // 
        // };

        // determine placement liste absolute
        // base.setAutocompletePositions = function()
        // {
        //     var autocomplete_top = $('input.c-displayer',  base.el).offset().top - $(window).scrollTop() + $('input.c-displayer',  base.el).outerHeight();
        //     var autocomplete_left = $('input.c-displayer',  base.el).offset().left ;

        //     $(base.checboxesWrapper).css({
        //         top: autocomplete_top, 
        //         left: autocomplete_left, 
        //     });
        // }
        
        base.highlightLine = function(elt)
        {
            elt.parents('.fr-fieldset__element').attr('aria-selected', function (i, attr) {
                return attr == 'true' ? 'false' : 'true'
            })
        }

        base.setResumeText = function()
        {
            var selected = [];
            $('input[type="checkbox"]', base.checboxesWrapper).each(function() {
                if ($(this).is(':checked')) {
                    selected.push($(this).parents('.fr-fieldset__element').find('label').text());
                }
            })

            var resumeText = '(' + selected.length + ') ';
            for (var i=0; i< selected.length; i++) {
                resumeText += selected[i];
                if (i < selected.length -1) {
                    resumeText += ', ';
                }
            }
            if (selected.length == 0) {
                resumeText = '';
            }

            if (resumeText == '') {
                resumeText = base.placeholder;
            }
            
            $(base.opener, base.el).text(resumeText);
        }

        base.toggleChecbkoxesWrapper = function()
        {
            if ($(base.checboxesWrapper).is(':visible')) {
                base.closeCheckboxesWrapper();
                $('#aid_search_categorysearch_autocomplete', base.checboxesWrapper).focus();
            } else {
                base.showCheckboxesWrapper();
            }
        }

        base.closeCheckboxesWrapper = function()
        {
            $(base.checboxesWrapper).hide();

            if(base.options.callbackcloseCheckboxesWrapper){
                base.options.callbackcloseCheckboxesWrapper.apply(this);
            }
        }

        base.showCheckboxesWrapper = function()
        {
            $(base.checboxesWrapper).show();
        }

        		// Ajout des écouteurs
		base.addListeners = function()
        {
            // $(window).on({
            //     scroll: function (e) {
            //         base.setAutocompletePositions();
            //     }
            // });
			
            // $(base.checboxesWrapper).on({
            //     keyup: function() {
            //         var thisElt = $(this);
        
            //         $('.fr-checkbox-group', thisElt.parents('.autocomplete-list-wrapper').find('.autocomplete-choices')).each(function() {
            //             if ($('label', this).text().toLowerCase().includes(base.getTextSearched()) || base.getTextSearched() == '' || $('input[type="checkbox"]', this).is(':checked')) {
            //                 $(this).parents('.fr-fieldset__element').show();
            //             } else {
            //                 $(this).parents('.fr-fieldset__element').hide();
            //             }
            //             base.handleDisplayOptgroup($(this).parents('.choices-optgroup'));
            //         })
            //     }
            // }, 'input.c-autocomplete');

            $(base.filter).on({
                click: function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                },
                keyup: function (e) {
                    var textSearch = $(this).val();
                    $('.fr-fieldset__element', $(this).parents('.checkboxes-wrapper')).each(function() {
                        if ($('label', this).text().toLowerCase().includes(textSearch.toLowerCase()) || $('input[type="checkbox"]', this).is(':checked')) {
                            $(this).show();
                        } else {
                            $(this).hide();
                        }
                    });
                }
            }, 'input');

            $(base.checboxesWrapper).on({
                change: function (e) {
                    if ($(this).attr('readonly')) {
                        if ($(this).is(':checked')) {
                            $(this).prop('checked', false);
                        } else {
                            $(this).prop('checked', true);
                        }
                        return;
                    }
                    base.setResumeText();
                    base.highlightLine($(this));
                }
            }, 'input[type="checkbox"]');

            $(base.el).on({
                click: function (e) {
                    base.toggleChecbkoxesWrapper();
                },
                focusin: function(e) {
                    base.showCheckboxesWrapper();
                }
            }, base.opener);

            $('body').on({
                click: function (e) {
                    if(
                        !(
                        ($(e.target).closest(base.checboxesWrapper).length > 0 )
                        || ($(e.target).closest(base.el).length > 0)
                        || ($(e.target).closest(base.filter).length > 0)
                        )
                    ){
                        base.closeCheckboxesWrapper();
                    }
                }
            });
		}

        // Run initializer
        base.init();
    };
    
    $.entity_checkbox_absolute_type.defaultOptions = {
        callbackcloseCheckboxesWrapper:	function(){},
    };
    
    $.fn.entity_checkbox_absolute_type = function(options){
        return this.each(function(){
            (new $.entity_checkbox_absolute_type(this, options));

		   // HAVE YOUR PLUGIN DO STUFF HERE
			
	
		   // END DOING STUFF

        });
    };
    
})(jQuery);