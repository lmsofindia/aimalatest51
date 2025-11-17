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
            const exploreItem = $('li[data-key="exploremenu"]');

            if (exploreItem.length && !$('#edz-explore-wrapper').length) {
                const html = `
                <div class="edzmenu-hover-container" id="edz-explore-wrapper">
                    ${exploreItem.prop('outerHTML')}
                    ${window.exploreMenuHTML}
                </div>`;
                exploreItem.replaceWith(html);
            }
            $(document).on('mouseenter', '.has-sub', function() {
                $(this).find('.submenu').stop(true, true).fadeIn(150);
            }).on('mouseleave', '.has-sub', function() {
                $(this).find('.submenu').stop(true, true).fadeOut(150);
            });
        }
    };
});
