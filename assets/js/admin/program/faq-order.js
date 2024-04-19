// Default SortableJS
import Sortable from 'sortablejs';

var nestedSortables = $('.nested-sortable');

$(function() {
    // Loop through each nested sortable element
    for (var i = 0; i < nestedSortables.length; i++) {
        new Sortable(nestedSortables[i], {
            group: {
                name: 'nested',
                pull: false // To prevent: Do not allow items to be pulled from this list.
            },
            animation: 150,
            fallbackOnBody: true,
            swapThreshold: 0.65
        });
    }

    $('.expandable-trigger').on({
        click: function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            $(this).parents('.expandable:first').toggleClass('expanded');
        }
    })

    $('#save-order').on({
        click: function(e) {
            e.preventDefault();
            e.stopPropagation();
            var items = [];

            $('.order-items').each(function() {
                var faq = $(this);
                var faqDict = {
                    'id': faq.attr('data-id'),
                    'entity': faq.attr('data-entity'),
                    'children': []
                }
                $('>li', faq).each(function() {
                    var faqCategory = $(this);
                    var faqCategoryDict = {
                        'id': faqCategory.find('span:first').attr('data-id'),
                        'entity': faqCategory.find('span:first').attr('data-entity'),
                        'children': []
                    }
                    
                    $('>ul li', faqCategory).each(function() {
                        var faqItem = $(this);
                        var faqItemDict = {
                            'id': faqItem.find('span:first').attr('data-id'),
                            'entity': faqCategory.find('span:first').attr('data-entity')
                        };
                        faqCategoryDict.children.push(faqItemDict);
                    });

                    faqDict.children.push(faqCategoryDict);
                })
                
                items.push(faqDict);
            });

            $('#orderToSave').val(JSON.stringify(items));
            $(this).parents('form').submit();
        }
    })
});
