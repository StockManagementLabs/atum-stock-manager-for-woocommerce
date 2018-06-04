// you can additionally search for the presence of a data attribute, irrespective of its value.
// https://stackoverflow.com/a/22209579
// http://jsfiddle.net/PTqmE/46/
// usage: $('div').filterByData('prop', 'val')

(function($) {

    $.fn.filterByData = function(prop, val) {
        var $self = this;
        if (typeof val === 'undefined') {
            return $self.filter(

                function() {
                    return typeof $(this).data(prop) !== 'undefined';
                });
        }
        return $self.filter(

            function() {
                return $(this).data(prop) == val;
            });
    };

})(window.jQuery);