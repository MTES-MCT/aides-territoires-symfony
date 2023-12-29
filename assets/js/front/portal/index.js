$(function () {
    $(document).on({
        keyup: function () {
            var val = $(this).val();
            $('a.fr-card__link').each(function () {
                var $this = $(this);
                if ($this.text().toLowerCase().indexOf(val.toLowerCase()) === -1) {
                    $this.closest('article').parent('div:first').hide();
                } else {
                    $this.closest('article').parent('div:first').show();
                }
            });
        }
    }, '#search');
});