<?php
namespace local_edzdashboardview\output;

defined('MOODLE_INTERNAL') || die();

/**
 * Renderer for local_edzdashboardview.
 */
class renderer extends \plugin_renderer_base {

    /**
     * Render dashboard with tabs.
     *
     * @return string
     */
    public function render_dashboard(): string {
        global $PAGE;

    
        // Attach JS modules.
        $PAGE->requires->js_call_amd('local_edzdashboardview/site', 'init');
        $PAGE->requires->js_call_amd('local_edzdashboardview/user', 'init');
        $PAGE->requires->js_call_amd('local_edzdashboardview/course', 'init');
        $PAGE->requires->js_call_amd('local_edzdashboardview/reward', 'init');

        // Tabs.
        $tabs = [
            [
                'id' => 'site',
                'label' => get_string('tabsite', 'local_edzdashboardview'),
                'active' => true,
                'content' => $this->render_from_template(
                    'local_edzdashboardview/site_tab',
                    ['cards' => $this->get_site_cards()]
                )
            ],
            [
                'id' => 'users',
                'label' => get_string('tabusers', 'local_edzdashboardview'),
                'active' => false,
                'content' => $this->render_from_template(
                    'local_edzdashboardview/users_tab',
                    [   // Default empty placeholders, JS will replace via web service
                        'cards' => [
                            'activeusers' => ['1day' => 0, '7day' => 0, '30day' => 0, '1year' => 0],
                            'enrolments'  => ['1day' => 0, '7day' => 0, '30day' => 0, '1year' => 0],
                            'registered'  => ['1day' => 0, '7day' => 0, '30day' => 0, '1year' => 0],
                            'webmobile'   => ['1day' => 0, '7day' => 0, '30day' => 0, '1year' => 0],
                        ]
                    ]
                )
            ],

            [
                'id' => 'courses',
                'label' => get_string('tabcourses', 'local_edzdashboardview'),
                'active' => false,
                'content' => $this->render_from_template(
                    'local_edzdashboardview/course_tab',
                    ['cards' => $this->get_site_cards()]
                )
            ],
            [
                'id' => 'rewards',
                'label' => get_string('tabrewards', 'local_edzdashboardview'),
                'active' => false,
                'content' => $this->render_from_template(
                    'local_edzdashboardview/reward_tab',
                    ['cards' => $this->get_site_cards()]
                )
            ],
        ];

        return $this->render_from_template('local_edzdashboardview/dashboard', [
            'tabs' => $tabs
        ]);
    }

    /**
     * Example site cards (static demo).
     *
     * @return array
     */
    private function get_site_cards(): array {
        return [
            'activeusers' => ['1day' => 123, '7day' => 456, '30day' => 789, '1year' => 999],
            'enrolments'  => ['1day' => 45,  '7day' => 67,  '30day' => 89,  '1year' => 120],
            'registered'  => ['1day' => 10,  '7day' => 20,  '30day' => 40,  '1year' => 100],
            'webmobile'   => ['1day' => 25,  '7day' => 50,  '30day' => 75,  '1year' => 200],
        ];
    }
}
