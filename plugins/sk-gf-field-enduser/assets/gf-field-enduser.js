'use strict';

jQuery(document).ready(function($) {
    $('.js-search-enduser').select2({
        placeholder: "Ange slutanvändare",
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
                    action: 'search_enduser',
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
