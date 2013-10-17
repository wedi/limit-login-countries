/**
 *
 * @package Limit_Login-Countries
 * @since 0.4
 */

jQuery(document).ready(function($) {

    $('#llc_blacklist').change(function() {

       $('label[for="llc_countries_js"]').text($.parseJSON(llc_countries_label)[$(this).val()]);
    });

    $('#llc_countries').hide().parent().append("<textarea id='llc_countries_js' name='llc_countries_js' rows='1' cols='52'></textarea>");

    $('label[for="llc_countries"]').attr('for', 'llc_countries_js');

    $('#llc_countries_js')
        .textext({
            plugins : 'autocomplete tags',
            tags: {
                items: $('#llc_countries').val().split(',')
            }
        })
        .bind('getSuggestions', function(e, data)
        {
            var list = $.parseJSON(llc_country_codes),
                textext = $(e.target).textext()[0],
                query = (data ? data.query : '') || ''
                ;

            $(this).trigger(
                'setSuggestions',
                { result : textext.itemManager().filter(list, query) }
            );
        })
        .bind('setFormData', function(e, data, isEmpty)
        {
            var textext = $(e.target).textext()[0];
            textext = textext.hiddenInput().val();
            $('#llc_countries').val(textext);
        });
});