<?php

use WP_Mock\Functions;
use WP_Mock\Tools\TestCase;


class LoginTest extends TestCase
{
    protected array $options = [
        'magic_option_name' => [
            'secret_key' => 'key',
            'redirect_uri' => '/',
            'user_role' => 'user_role',
            'wc_login' => true,
            'admin_login' => true,
        ],
    ];

    public function setUp(): void
    {
        parent::setUp();

        WP_Mock::userFunction('plugin_dir_path', [
            'args' => [Functions::type('string')],
            'return' => dirname(__FILE__, 2) . '/'
        ]);

        WP_Mock::userFunction('plugin_dir_url', [
            'args' => [Functions::type('string')],
            'return' => '/'
        ]);

        WP_Mock::userFunction('get_option', [
            'args' => [Functions::type('string')],
            'return' => $this->options['magic_option_name']
        ]);

        WP_Mock::userFunction('add_shortcode', [
            'args' => [Functions::type('string'), Functions::type('array')]
        ]);

        WP_Mock::userFunction('do_shortcode', [
            'args' => [Functions::type('string')],
            'return' => 'shortcode result'
        ]);

        WP_Mock::userFunction('site_url', [
            'return' => '/'
        ]);

        WP_Mock::userFunction('get_rest_url', [
            'return' => '/'
        ]);

        WP_Mock::userFunction('add_query_arg', [
            'return_arg' => 1
        ]);

        $wp_user = $this->getMockBuilder(WP_User::class)->getMock();
        $wp_user->user_login = 'test';
        $data = new stdClass();
        $data->ID = '10';
        $wp_user->data = $data;

        WP_Mock::userFunction('get_user_by', [
            'args' => [Functions::type('string'), '*'],
            'return' => $wp_user
        ]);

        WP_Mock::userFunction('check_password_reset_key', [
            'args' => ['*', '*'],
            'return' => $wp_user
        ]);

        WP_Mock::userFunction('is_wp_error', [
            'args' => ['*'],
            'return' => false
        ]);

        WP_Mock::userFunction('wp_set_auth_cookie', [
            'args' => ['*', '*', '*']
        ]);

        WP_Mock::userFunction('wp_unslash');
    }

    public function tearDown(): void
    {
        parent::tearDown();
    }

    /**
     * @dataProvider pageNowProvider
     */
    public function test_magic_wp_login($page_now, $output_expected): void
    {
        $GLOBALS['pagenow'] = $page_now;

        $ml = new Magic_Login();

        $this->expectOutputRegex('/.*/');
        $ml->magic_wp_login();
        $output = $this->getActualOutput();
        if ($output_expected) {
            $this->assertNotEmpty($output);
        } else {
            $this->assertEmpty($output);
        }
    }

    public function pageNowProvider(): array
    {
        return [
            ['wp-login.php', true],
            ['', false]
        ];
    }

    /**
     * @dataProvider locateTemplateProvider
     */
    public function test_magic_locate_template($tmpl, $rewrite_expected)
    {
        $ml = new Magic_Login();

        $new_tmpl = $ml->magic_locate_template($tmpl, '', '');
        if ($rewrite_expected) {
            $this->assertNotEquals($tmpl, $new_tmpl);
        } else {
            $this->assertEquals($tmpl, $new_tmpl);
        }
    }

    public function locateTemplateProvider(): array
    {
        return [
            ['form-login.php', true],
            ['wp-login.php', false],
            ['', false]
        ];
    }

    public function test_authorize_user()
    {
        global $_GET;
        $_GET['key'] = 'key';
        $_GET['email'] = 'test@test.test';

        WP_Mock::userFunction('wp_redirect',
            [
                'times' => 1,
                'args' => [Functions::type('string')],
                'return' => true
            ]
        );
        WP_Mock::userFunction('wp_die',
            [
                'times' => 1
            ]
        );

        $ml = new Magic_Login();
        $ml->authorize_user();
    }

    public function test_no_authorize_user()
    {
        unset($_GET['key'], $_GET['email']);
        WP_Mock::userFunction('wp_redirect',
            [
                'times' => 0,
                'args' => [Functions::type('string')],
                'return' => true
            ]
        );
        WP_Mock::userFunction('wp_die',
            [
                'times' => 0
            ]
        );

        $this->expectOutputRegex('/.*/');

        $ml = new Magic_Login();
        $ml->authorize_user();
    }

    public function test_register_routes()
    {
        WP_Mock::userFunction('register_rest_route',
            [
                'times' => '1+',
                'args' => [Functions::type('string'), Functions::type('string'), Functions::type('array')],
                'return' => true
            ]
        );

        $ml = new Magic_Login();
        $ml->register_routes();
    }

    public function test_get_auth_link()
    {
        $ml = $this->getMockBuilder(Magic_Login::class)
            ->onlyMethods(['get_login_url'])
            ->getMock();

        $ml->method('get_login_url')
            ->willReturn('/');

        $reflection = new ReflectionClass(Magic_Login::class);
        $reflection_property = $reflection->getProperty('user_email');
        $reflection_property->setAccessible(true);

        $reflection_property->setValue($ml, 't@t.test');

//        $ml = new Magic_Login();
        $res = $ml->get_auth_link();
        $this->assertIsString($res);
    }

    public function test_get_login_url()
    {
        WP_Mock::userFunction('get_password_reset_key',
            [
                'times' => '1',
                'args' => [Functions::type('object')],
                'return' => true
            ]
        );

        $wp_user = $this->getMockBuilder(WP_User::class)->getMock();
        $wp_user->user_login = 'test';
        $wp_user->user_email = 'test@test.test';
        $data = new stdClass();
        $data->ID = '10';
        $wp_user->data = $data;

        $ml = new Magic_Login();
        $result = $ml->get_login_url($wp_user);
        $this->assertIsString($result);
    }

    /**
     * @dataProvider isUserLoggedInProvider
     */
    public function test_display_magic_login($is_logged)
    {
        WP_Mock::userFunction('is_user_logged_in',
            [
                'return' => $is_logged
            ]
        );

        $ml = $this->getMockBuilder(Magic_Login::class)
            ->onlyMethods(['get_template_content'])
            ->getMock();

        $ml->method('get_template_content')
            ->with($this->isType('string'))
            ->willReturn('<p>html</p>');

        $result = $ml->display_magic_login();
        $this->assertIsString($result);
    }

    public function isUserLoggedInProvider(): array
    {
        return [[true], [false]];
    }

    public function test_add_login_scripts()
    {
        WP_Mock::userFunction('wp_register_script',
            [
                'times' => '1+'
            ]
        );
        WP_Mock::userFunction('wp_enqueue_script',
            [
                'times' => '1+'
            ]
        );
        WP_Mock::userFunction('wp_enqueue_style',
            [
                'times' => '1+'
            ]
        );
        WP_Mock::userFunction('wp_localize_script',
            [
                'times' => '1+'
            ]
        );

        $ml = new Magic_Login();
        $ml->add_login_scripts();
    }

    public function test_render()
    {
        $method = new ReflectionMethod(Magic_Login::class, 'render');
        $method->setAccessible(true);

        $ml = new Magic_Login();
        $this->expectOutputRegex('/.*/');
        $method->invoke($ml);
    }
}
