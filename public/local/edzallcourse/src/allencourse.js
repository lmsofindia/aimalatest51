/**
 * JavaScript for the explore button panel toggle.
 *
 * @module     local_customcatmenu/explore
 * @copyright  2025 Rashid <abdul.rashid@edzlms.com>
 * @license    https://edzlms.com/
 */

  define(['jquery'], function($) {
    return {
        init: function() {
            const allEnItem = $('li[data-key="edzallencou"]');

            if (allEnItem.length && !$('#edz-allencou-wrapper').length) {
                const html = `
                <div class="edzallencou-hover-container" id="edz-allencou-wrapper">
                    ${allEnItem.prop('outerHTML')}
                    ${window.allEnCourseMenuHTML}
                </div>`;
                allEnItem.replaceWith(html);
            }

            $(document).on('mouseenter', '.edzallencou-has-sub', function() {
                $(this).find('.edzallencou-submenu').stop(true, true).fadeIn(150);
            }).on('mouseleave', '.edzallencou-has-sub', function() {
                $(this).find('.edzallencou-submenu').stop(true, true).fadeOut(150);
            });
        }
    };
});
