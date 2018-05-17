/**
 * Atum Stock Central SC
 *
 * @copyright Stock Management Labs ©2017
 *  @since 1.4.6
 */

(function ($) {
    'use strict';

    $(function () {
        //
        // Init stickyHeaders: floatThead
        //--------------------------------

        $('.atum-list-table').floatThead({
            responsiveContainer: function ($table) {
                return $table.closest('.jspContainer');
            },
            position: 'absolute'
        });
    });
})(jQuery);

jQuery.noConflict();
