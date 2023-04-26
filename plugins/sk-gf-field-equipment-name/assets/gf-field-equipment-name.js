'use strict';

jQuery(document).ready(function($) {
    $('.js-search-equipment-name').select2({
        placeholder: "Ange datornamn",
        allowClear: true,
        delay: 250,
        minimumInputLength: 5,
        language: "sv",
        width: '100%',
        data: [
            {
                id: localStorage.getItem('equipment_name_id'),
                text: localStorage.getItem('equipment_name')
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
        localStorage.removeItem('equipment_name_id');
        localStorage.removeItem('equipment_name');
    }, 1000 * 60 * 4);

    $('.js-search-equipment-name').val(localStorage.getItem('equipment_name_id'));
    $('.js-search-equipment-name').trigger('change');
});

jQuery(document).ready(function($) {
    $('.js-search-equipment-name').on('select2:open', function (e) {
        $('.js-search-equipment-name').val(null).trigger('change');
        localStorage.removeItem('equipment_name_id');
        localStorage.removeItem('equipment_name');
    });

    $('.js-search-equipment-name').on('select2:select', function (e) {
        var data = e.params.data;
        localStorage.setItem('equipment_name_id', data.id);
        localStorage.setItem('equipment_name', data.text);
        $('.js-search-equipment-name').val(data.id);
        $('.js-search-equipment-name').trigger('change');
    });

});