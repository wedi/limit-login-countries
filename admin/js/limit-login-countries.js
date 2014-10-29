/**
 * Some JavaScript to do some fancy stuff on our options page in WP's admin area
 *
 * @package Limit Login Countries
 * @author Dirk Weise
 * @since 0.4
 */

jQuery( document ).ready( function( $ ) {
    "use strict";

    /* Define globals acceptable for JSHint */
    /* global LLC_COUNTRIES_LABEL: false, LLC_COUNTRY_CODES: false, LLC_AYS: false */

    $( "#llc-options-page form").areYouSure( { "message": LLC_AYS.message } );

    // Move .updated-nag alert boxes. Don't move boxes designed to be inline.
    // copied from wp-admin/js/common.js:354
    $( "div.wrap h2:first" ).nextAll( "div.update-nag" ).addClass( "below-h2" );
    $( "div.update-nag" ).not( ".below-h2, .inline" ).insertAfter( $( "div.wrap h2:first" ) );

    $( "#llc_blacklist" ).change( function() {
        // we change the label of country list according to whitelist or blacklist mode
        $( "label[for='llc_countries_js']" ).text( LLC_COUNTRIES_LABEL[ $( this ).val() ] );
    } );

    // we hide the non JS text input and add the TextExt textarea.
    $( "#llc_countries" ).hide().after(
        "<textarea id='llc_countries_js' name='llc_countries_js' rows='1' cols='52'></textarea>"
    );

    // we change the country list label to reference our new country list input
    $( "label[for='llc_countries']" ).attr( "for", "llc_countries_js" );

    // hide elements with .no-js class when javascript is available
    $( "#llc-options-page .no-js" ).hide();

    // we initialize the new country list input
    // TODO: Do this on element creation
    var countryInput = $( "#llc_countries" );
    if ( countryInput.length ) {
        $( "#llc_countries_js" )
            .textext( {
                plugins: "autocomplete tags",
                tags: {
                    // we preset the list with the current value from no-js text input
                    items: countryInput.val().split( "," )
                }
            } )
            .bind( "getSuggestions", function( e, data ) {
                var list = LLC_COUNTRY_CODES,
                    textext = $( e.target ).textext()[ 0 ],
                    query = ( data ? data.query : "" ) || ""
                    ;

                $( this ).trigger(
                    "setSuggestions",
                    { result: textext.itemManager().filter( list, query ) }
                );
            } )
            // we write all changes in our new input to the old one for submission
            .bind( "setFormData", function( e ) {
                var textext = $( e.target ).textext()[ 0 ];
                textext = textext.hiddenInput().val();
                $( "#llc_countries" ).val( textext );
            } );
    } // if( country_input.length )
} );
