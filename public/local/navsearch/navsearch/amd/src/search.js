define(['jquery'], function($) {

    function bindSearch($input, $results, $clear) {
        $input.on('keyup', function() {
            const q = $(this).val().trim();

            if (q.length > 0) {
                $clear.show();
            } else {
                $clear.hide();
            }

            if (q.length < 2) {
                $results.empty().hide();
                return;
            }

            // 🔹 AJAX search request
            $.get(M.cfg.wwwroot + '/local/navsearch/search.php', {query: q}, function(data) {
                $results.html(data).show();
            });
        });

        $clear.on('click', function() {
            $input.val('');
            $results.empty().hide();
            $clear.hide();
            $input.focus();
        });
    }

    return {
        init: function() {
            var container = $('.navsearch-container');
            var toggleBtn = $('#navsearchtoggle');
            var input     = $('#navsearchbox');
            var clearBtn  = $('#navsearchclear');
            var results   = $('#navsearchresults');

            // Toggle dropdown
            toggleBtn.on('click', function(e) {
                e.preventDefault();
                container.toggleClass('expanded collapsed');
                if (container.hasClass('expanded')) {
                    input.focus();
                }
            });

            // 🔹 Attach search binding
            if (input.length && results.length && clearBtn.length) {
                bindSearch(input, results, clearBtn);
            }

            // Close when clicking outside
            $(document).on('click', function(e) {
                if (!container.is(e.target) && container.has(e.target).length === 0) {
                    container.removeClass('expanded').addClass('collapsed');
                }
            });
        }
    };
});
