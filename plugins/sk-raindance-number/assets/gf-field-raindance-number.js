'use strict';

jQuery(document).ready(function($) {
    $('.js-search-raindance-number').select2({
        placeholder: "Ange ett nummer som ska kontrolleras i Raindance",
        allowClear: true,
        delay: 250,
        minimumInputLength: 3,
        language: "sv",
        width: '100%',
        ajax: {
            type: 'POST',
            url: ajax.url,
            data: function (params) {
                return {
                    action: 'search_raindance-number',
                    s: params.term
                }
            },
            dataType: 'json',
            processResults: function (data) {
                // Transforms the top-level key of the response object from 'items' to 'results'
                return {
                  results: data
                };
              }
        }
    });
});