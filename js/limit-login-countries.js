/**
 * Some JavaScript to do some fancy stuff on our options page in WP's admin area
 *
 * @package Limit Login Countries
 * @author Dirk Weise
 * @since 0.4
 */

jQuery(document).ready(function($) {

    $('#llc_blacklist').change(function() {
        // we change the label of country list according to whitelist or blacklist mode
       $('label[for="llc_countries_js"]').text($.parseJSON(llc_countries_label)[$(this).val()]);
    });

    $('#llc_countries').hide().parent().append("<textarea id='llc_countries_js' name='llc_countries_js' rows='1' cols='52'></textarea>");
    // we hide the non JS text input and add the TextExt textarea.

    // we change the country list label to reference our new country list input
    $('label[for="llc_countries"]').attr('for', 'llc_countries_js');

    // hide elements with .no-js class when javascript is available
    $('#llc-options-page .no-js').hide();

    // we initialize the new country list input
    // TODO: Do this on element creation
    $('#llc_countries_js')
        .textext({
            plugins : 'autocomplete tags',
            tags: {
                // we preset the list with the current value from no-js text input
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
        // we write all changes in our new input to the old one for submission
        .bind('setFormData', function(e, data, isEmpty)
        {
            var textext = $(e.target).textext()[0];
            textext = textext.hiddenInput().val();
            $('#llc_countries').val(textext);
        });
});