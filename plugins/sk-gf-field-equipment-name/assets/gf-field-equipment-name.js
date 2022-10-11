'use strict';

jQuery(document).ready(function($) {
    $('.js-search-computer').select2({
        placeholder: "Ange datornamn",
        allowClear: true,
        delay: 250,
        minimumInputLength: 5,
        language: "sv",
        width: '100%',
        ajax: {
            type: 'POST',
            url: ajax.url,
            data: function (params) {
                return {
                    action: 'search_equipment_name',
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
