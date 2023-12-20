(function($){
    $.checkbox_multiple_search = function(el, options){
        // To avoid scope issues, use 'base' instead of 'this'
        // to reference this class from internal events and functions.
        var base = this;
        
        // Access to jQuery and DOM versions of element
        base.$el = $(el);
        base.el = el;
        
        // Add a reverse reference to the DOM object
        base.$el.data("checkbox_multiple_search", base);
        
        base.init = function(){
            
            base.thisAutocompleteListWrapper = base.$el.find('.autocomplete-list-wrapper');
            base.options = $.extend({},$.checkbox_multiple_search.defaultOptions, options);
            
            // Put your initialization code here
            // placement de la liste en absolute
            base.setAutocompletePositions();

            // les ecouteurs
            base.addListeners();
        };
        
        // Sample Function, Uncomment to use
        // base.functionName = function(paramaters){
        // 
        // };

        // determine placement liste absolute
        base.setAutocompletePositions = function()
        {
            var autocomplete_top = $('input.c-displayer',  base.el).offset().top - $(window).scrollTop() + $('input.c-displayer',  base.el).outerHeight();
            var autocomplete_left = $('input.c-displayer',  base.el).offset().left ;

            $(base.thisAutocompleteListWrapper).css({
                top: autocomplete_top, 
                left: autocomplete_left, 
            });
        }

        base.handleDisplayOptgroup = function(elt)
        {
            var toShow = false;
            $('.fr-fieldset__element:not(".optgroup")', elt).each(function() {
                if ($(this).css('display') !== 'none' || $(this).is(':checked')) {
                    toShow = true;
                    return;
                }
            })

            if (toShow) {
                elt.show();
            } else {
                elt.hide();
            }
        }
        
        base.highlightLine = function(elt)
        {
            elt.parents('.fr-fieldset__element').attr('aria-selected', function (i, attr) {
                return attr == 'true' ? 'false' : 'true'
            })
        }

        base.setResumeText = function()
        {
            var selected = [];
            $('input[type="checkbox"]', base.thisAutocompleteListWrapper).each(function() {
                if ($(this).is(':checked')) {
                    selected.push($(this).parents('.fr-fieldset__element').find('.at-field-label').text());
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
            
            $('input.c-displayer', base.el).val(resumeText);
        }

        base.getTextSearched = function()
        {
            return $('input.c-autocomplete', base.thisAutocompleteListWrapper).val().toLowerCase();
        }

        base.toggleAutocompleteList = function()
        {
            if ($(base.thisAutocompleteListWrapper).is(':visible')) {
                base.closeAutocompleteList();
                $('#aid_search_categorysearch_autocomplete', base.thisAutocompleteListWrapper).focus();
            } else {
                base.showAutocompleteList();
            }
        }

        base.closeAutocompleteList = function()
        {
            $(base.thisAutocompleteListWrapper).hide();

            if(base.options.callbackCloseAutocompleteList){
                base.options.callbackCloseAutocompleteList.apply(this);
            }
        }

        base.showAutocompleteList = function()
        {
            $(base.thisAutocompleteListWrapper).show();
        }

        		// Ajout des écouteurs
		base.addListeners = function()
        {
            $(window).on({
                scroll: function (e) {
                    base.setAutocompletePositions();
                }
            });
			
            $(base.thisAutocompleteListWrapper).on({
                keyup: function() {
                    var thisElt = $(this);
        
                    $('.fr-checkbox-group', thisElt.parents('.autocomplete-list-wrapper').find('.autocomplete-choices')).each(function() {
                        if ($('label', this).text().toLowerCase().includes(base.getTextSearched()) || base.getTextSearched() == '' || $('input[type="checkbox"]', this).is(':checked')) {
                            $(this).parents('.fr-fieldset__element').show();
                        } else {
                            $(this).parents('.fr-fieldset__element').hide();
                        }
                        base.handleDisplayOptgroup($(this).parents('.choices-optgroup'));
                    })
                }
            }, 'input.c-autocomplete');

            $(base.thisAutocompleteListWrapper).on({
                change: function (e) {
                    base.setResumeText();
                    base.highlightLine($(this));
                }
            }, 'input[type="checkbox"]');

            $(base.el).on({
                click: function (e) {
                    base.setAutocompletePositions();
                    base.toggleAutocompleteList();
                }
            }, 'input.c-displayer');

            $('body').on({
                click: function (e) {
                    if(!(($(e.target).closest(base.thisAutocompleteListWrapper).length > 0 ) || ($(e.target).closest(base.el).length > 0))){
                        base.closeAutocompleteList();
                    }
                }
            });
		}

        // Run initializer
        base.init();
    };
    
    $.checkbox_multiple_search.defaultOptions = {
        callbackCloseAutocompleteList:	function(){},
    };
    
    $.fn.checkbox_multiple_search = function(options){
        return this.each(function(){
            (new $.checkbox_multiple_search(this, options));

		   // HAVE YOUR PLUGIN DO STUFF HERE
			
	
		   // END DOING STUFF

        });
    };
    
})(jQuery);