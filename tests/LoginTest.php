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
//
//        WP_Mock::userFunction('add_shortcode', [
//            'args' => [Functions::type('string'), Functions::type('array')]
//        ]);

        WP_Mock::userFunction('login_header');

        WP_Mock::userFunction('do_shortcode', [
            'args' => [Functions::type('string')],
            'return' => 'shortcode result'
        ]);


        WP_Mock::userFunction('get_rest_url', [
            'return' => '/'
        ]);

        WP_Mock::userFunction('add_query_arg', [
            'return_arg' => 1
        ]);
//
//        $wp_user = $this->getMockBuilder(WP_User::class)->getMock();
//        $wp_user->user_login = 'test';
//        $data = new stdClass();
//        $data->ID = '10';
//        $wp_user->data = $data;
//
//        WP_Mock::userFunction('get_user_by', [
//            'args' => [Functions::type('string'), '*'],
//            'return' => $wp_user
//        ]);
//
//        WP_Mock::userFunction('check_password_reset_key', [
//            'args' => ['*', '*'],
//            'return' => $wp_user
//        ]);
//
//        WP_Mock::userFunction('is_wp_error', [
//            'args' => ['*'],
//            'return' => false
//        ]);
//
//        WP_Mock::userFunction('wp_set_auth_cookie', [
//            'args' => ['*', '*', '*']
//        ]);

        WP_Mock::passthruFunction('site_url');
        WP_Mock::passthruFunction('wp_unslash');
    }

    public function tearDown(): void
    {
        parent::tearDown();
    }

    public function test_Magic_Login__construct()
    {
        WP_Mock::userFunction('add_shortcode', [
            'times' => '1+'
        ]);

        $prop_names = ['path', 'url', 'secret_key', 'redirect_url', 'user_role'];

        $ml = new Magic_Login();

        $reflection = new ReflectionClass(Magic_Login::class);
        foreach ($prop_names as $prop_name) {
            $reflection_property = $reflection->getProperty($prop_name);
            $reflection_property->setAccessible(true);
            $this->assertNotNull($reflection_property->getValue($ml), 'не установленна переменная ' . $prop_name);
        }
    }

    /**
     * @dataProvider pageNowProvider
     */
    public function test_magic_wp_login($page_now, $is_user_logged_in, $output_expected): void
    {
        $GLOBALS['pagenow'] = $page_now;

        WP_Mock::userFunction('is_user_logged_in', [
            'return' => $is_user_logged_in,
        ]);

        $ml = $this->getMockBuilder(Magic_Login::class)
            ->onlyMethods(['exit'])
            ->getMock();

        if ($output_expected) {
            $ml->expects($this->once())
                ->method('exit');
        }

        $this->expectOutputRegex('/.*/');
        $ml->magic_wp_login();
        $output = $this->getActualOutput();
        if ($output_expected) {
            $this->assertNotEmpty($output, 'The login form was not displayed');
        } else {
            $this->assertEmpty($output, 'The login form was displayed incorrectly');
        }
    }

    public function pageNowProvider(): array
    {
        return [
            ['wp-login.php', true, false],
            ['wp-login.php', false, true],
            ['post.php', true, false],
            ['post.php', false, false],
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

    /**
     * @dataProvider authorizeUserProvider
     */
    public function test_authorize_user($key, $email, $is_user_exist, $is_key_valid, $is_ok)
    {
        global $_GET;
        $_GET['key'] = $key;
        $_GET['email'] = $email;

        $wp_error = $this->getMockBuilder(WP_Error::class)->getMock();
        $wp_error->get_error_message = function ($code = '') {
            return 'Test error message.';
        };

        if ($is_user_exist) {
            $wp_user = $this->getMockBuilder(WP_User::class)->getMock();
            $wp_user->user_login = 'test_login';
            $data = new stdClass();
            $data->ID = '10';
            $wp_user->data = $data;
        } else {
            $wp_user = false;
        }

        WP_Mock::userFunction('get_user_by', [
            'return' => $wp_user
        ]);

        WP_Mock::userFunction('check_password_reset_key', [
            'args' => [$key, 'test_login'],
            'return' => $is_key_valid ? $wp_user : $wp_error,
        ]);

        WP_Mock::userFunction('check_password_reset_key', [
            'args' => ['*', '*'],
            'return' => $wp_error,
        ]);

        WP_Mock::userFunction('is_wp_error', [
            'args' => [$wp_error],
            'return' => true,
        ]);

        WP_Mock::userFunction('is_wp_error', [
            'args' => ['*'],
            'return' => false,
        ]);

        WP_Mock::userFunction('wp_set_auth_cookie', [
            'times' => $is_ok ? 1 : 0
        ]);

        WP_Mock::userFunction('wp_redirect', [
            'times' => $is_ok ? 1 : 0,
            'args' => [Functions::type('string')],
            'return' => true
        ]);

        $ml = $this->getMockBuilder(Magic_Login::class)
            ->onlyMethods(['exit'])
            ->getMock();

        if ($is_ok) {
            $ml->expects($this->once())
                ->method('exit');
        }

        $this->expectOutputRegex('/.*/');

        $ml->authorize_user();

        unset($_GET['key'], $_GET['email']);
    }

    public function authorizeUserProvider(): array
    {
        return [
            ['key', 'test@test.test', true, true, true],
            ['key', 'test@test.test', true, false, false],
            ['key', 'test@test.test', false, true, false],
            ['key', 'test@test.test', false, false, false],

            [null, 'test@test.test', true, true, false],
            [null, 'test@test.test', true, false, false],
            [null, 'test@test.test', false, true, false],
            [null, 'test@test.test', false, false, false],

            ['key', null, true, true, false],
            ['key', null, true, false, false],
            ['key', null, false, true, false],
            ['key', null, false, false, false],
        ];
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

    /**
     * @dataProvider authLinkProvider
     */
    public function test_get_auth_link($is_user_exist, $create_user_successfully)
    {
        $wp_error = $this->getMockBuilder(WP_Error::class)->getMock();
        $wp_error->get_error_message = function ($code = '') {
            return 'Test error message.';
        };

        $wp_user = $this->getMockBuilder(WP_User::class)->getMock();
        $wp_user->user_login = 'test_login';
        $data = new stdClass();
        $data->ID = '10';
        $wp_user->data = $data;


        WP_Mock::userFunction('get_user_by', [
            'args' => ['email', '*'],
            'return' => $is_user_exist ? $wp_user : false
        ]);
        WP_Mock::userFunction('get_user_by', [
            'return' => $wp_user
        ]);

        WP_Mock::userFunction('wp_create_user', [
            'return' => $create_user_successfully ? $wp_user : $wp_error
        ]);

        WP_Mock::userFunction('wp_generate_password', [
            'return' => 'too_hard_pass_1'
        ]);
        WP_Mock::userFunction('is_wp_error', [
            'args' => [$wp_error],
            'return' => true,
        ]);
        WP_Mock::userFunction('is_wp_error', [
            'args' => ['*'],
            'return' => false,
        ]);

        $ml = $this->getMockBuilder(Magic_Login::class)
            ->onlyMethods(['get_login_url'])
            ->getMock();

        $ml->method('get_login_url')
            ->willReturn('/');

        $reflection = new ReflectionClass(Magic_Login::class);
        $reflection_property = $reflection->getProperty('user_email');
        $reflection_property->setAccessible(true);

        $reflection_property->setValue($ml, 't@t.test');

        $res = $ml->get_auth_link();
        if ($is_user_exist || $create_user_successfully) {
            $this->assertIsString($res);
        } else {
            $this->assertFalse($res);
        }
    }

    public function authLinkProvider()
    {
        return [
            [true, true],
            [true, false],
            [false, true],
            [false, false],
        ];
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
        WP_Mock::userFunction('wp_register_script', [
            'times' => '1+'
        ]);
        WP_Mock::userFunction('wp_enqueue_script', [
            'times' => '1+'
        ]);
        WP_Mock::userFunction('wp_enqueue_style', [
            'times' => '1+'
        ]);
        WP_Mock::userFunction('wp_localize_script', [
            'times' => '1+'
        ]);

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
