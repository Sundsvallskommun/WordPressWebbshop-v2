'use strict';

jQuery(document).ready(function($) {
    $('.js-search-computer').select2({
        placeholder: "Ange datornamn",
        allowClear: true,
        delay: 250,
        minimumInputLength: 5,
        language: "sv",
        width: '100%',
        data: [
            {
                id: localStorage.getItem('computer_name_id'),
                text: localStorage.getItem('computer_name')
            }
        ],
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

    setTimeout(function() {
        localStorage.removeItem('computer_name_id');
        localStorage.removeItem('computer_name');
    }, 1000 * 60 * 4);

    $('.js-search-computer').val(localStorage.getItem('computer_name_id'));
    $('.js-search-computer').trigger('change');
});

jQuery(document).ready(function($) {
    $('.js-search-computer').on('select2:open', function (e) {
        $('.js-search-computer').val(null).trigger('change');
        localStorage.removeItem('computer_name_id');
        localStorage.removeItem('computer_name');
    });

    $('.js-search-computer').on('select2:select', function (e) {
        var data = e.params.data;
        localStorage.setItem('computer_name_id', data.id);
        localStorage.setItem('computer_name', data.text);
        $('.js-search-computer').val(data.id);
        $('.js-search-computer').trigger('change');
    });

});