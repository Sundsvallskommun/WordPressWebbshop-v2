jQuery(document).ready(function($) {

    var containerSelector = '.js-megamenu'
        buttonSelector = '.js-category-menu';

    $(document).mouseup(function(e) {
        var $container = $(containerSelector);
        var $button = $(buttonSelector);

        // if the target of the click isn't the container nor a descendant of the container
        if ( (!$container.is(e.target) && $container.has(e.target).length === 0) && ( !$button.is(e.target) && $button.has(e.target).length === 0) ) {
            $container.hide();
        }
    });

    $( buttonSelector ).on( 'click', function(e) {
        e.preventDefault();
        $(containerSelector).toggle();
    });
    
    const MegaMenu = new Vue({
        el: ".js-megamenu",
        data: {
            activeTab: 9
        }
    })

});