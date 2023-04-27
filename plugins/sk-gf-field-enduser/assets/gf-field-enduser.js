'use strict';

jQuery(document).ready(function($) {
    $('.js-search-enduser').select2({
        placeholder: "Ange slutanvändare",
        allowClear: true,
        delay: 250,
        minimumInputLength: 3,
        language: "sv",
        width: '100%',
        data: [
            {
                id: localStorage.getItem('enduser_id'),
                text: localStorage.getItem('enduser_name')
            }
        ],
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
    
    setTimeout(function() {
        localStorage.removeItem('enduser_id');
        localStorage.removeItem('enduser_name');
    }, 1000 * 60 * 4);

    $('.js-search-enduser').val(localStorage.getItem('enduser_id'));
    $('.js-search-enduser').trigger('change');
});

jQuery(document).ready(function($) { 
    $('.js-search-enduser').on('select2:open', function (e) {
        $('.js-search-enduser').val(null).trigger('change');
        localStorage.removeItem('enduser_id');
        localStorage.removeItem('enduser_name');

    });

    $('.js-search-enduser').on('select2:select', function (e) {
        var data = e.params.data;
        localStorage.setItem('enduser_id', data.id);
        localStorage.setItem('enduser_name', data.text);
        $('.js-search-enduser').val(data.id);
        $('.js-search-enduser').trigger('change');
    });

});