<?php


use WP_Mock\Functions;
use WP_Mock\Tools\TestCase;


class AdminTest extends TestCase
{
    protected array $options = [
        'magic_option_name' => [
            'publishable_key' => 'pub_key',
            'secret_key' => 'key',
            'redirect_uri' => '/',
            'user_role' => 'user_role',
            'wc_login' => 'yes',
            'admin_login' => 'yes',
        ],
    ];

    public function setUp(): void
    {
        parent::setUp();

        WP_Mock::userFunction('get_option', [
            'args' => [Functions::type('string')],
            'return' => $this->options['magic_option_name']
        ]);
        WP_Mock::userFunction('esc_html__', [
            'args' => [Functions::type('string'), Functions::type('string')],
            'return_arg' => 0
        ]);
        WP_Mock::userFunction('esc_attr', [
            'args' => [Functions::type('string')],
            'return_arg' => 0
        ]);
    }

    public function tearDown(): void
    {
        parent::tearDown();
    }

    public function test_magic_add_plugin_page()
    {
        WP_Mock::userFunction('add_options_page', [
            'times' => 1
        ]);

        $ma = new Magic_Admin;
        $ma->magic_add_plugin_page();
    }

    public function test_magic_create_admin_page()
    {
        WP_Mock::userFunction('settings_errors');
        WP_Mock::userFunction('settings_fields');
        WP_Mock::userFunction('do_settings_sections');
        WP_Mock::userFunction('submit_button');

        $this->expectOutputRegex('/.*/');

        $ma = new Magic_Admin;
        $ma->magic_create_admin_page();
    }

    public function test_magic_page_init()
    {
        WP_Mock::userFunction('register_setting', [
            'times' => 1
        ]);
        WP_Mock::userFunction('add_settings_section', [
            'times' => 1
        ]);
        WP_Mock::userFunction('add_settings_field', [
            'times' => '1+'
        ]);

        $ma = new Magic_Admin;
        $ma->magic_page_init();
    }

    public function test_magic_sanitize()
    {
        $count_fields = count($this->options['magic_option_name']);
        WP_Mock::userFunction('sanitize_text_field', [
            'args' => [Functions::type('string')],
            'times' => $count_fields,
            'return_arg' => 0
        ]);

        $ma = new Magic_Admin;
        $result = $ma->magic_sanitize($this->options['magic_option_name']);
        $this->assertIsArray($result);
    }

    public function test_magic_section_info()
    {
        $ma = new Magic_Admin;
        $ma->magic_section_info();
    }

    public function test_publishable_key_callback()
    {
        $ma = $this->getMockBuilder(Magic_Admin::class)->getMock();

        $reflection = new ReflectionClass(Magic_Admin::class);
        $reflection_property = $reflection->getProperty('magic_options');
        $reflection_property->setAccessible(true);

        $reflection_property->setValue($ma, $this->options);

        $this->expectOutputRegex('/.*/');

        $ma->publishable_key_callback();
    }

    public function test_secret_key_callback()
    {
        $ma = $this->getMockBuilder(Magic_Admin::class)->getMock();

        $reflection = new ReflectionClass(Magic_Admin::class);
        $reflection_property = $reflection->getProperty('magic_options');
        $reflection_property->setAccessible(true);

        $reflection_property->setValue($ma, $this->options);

        $this->expectOutputRegex('/.*/');

        $ma->secret_key_callback();
    }

    public function test_redirect_uri_callback()
    {
        $ma = $this->getMockBuilder(Magic_Admin::class)->getMock();

        $reflection = new ReflectionClass(Magic_Admin::class);
        $reflection_property = $reflection->getProperty('magic_options');
        $reflection_property->setAccessible(true);

        $reflection_property->setValue($ma, $this->options);

        $this->expectOutputRegex('/.*/');

        $ma->redirect_uri_callback();
    }

    public function test_user_role_callback()
    {
        global $wp_roles;
        $wp_roles = new stdClass();
        $wp_roles->roles = [
            ['name' => 'user'],
            ['name' => 'admin']
        ];

        WP_Mock::userFunction('selected', [
            'return_in_order' => [' selected', '']
        ]);

        $ma = $this->getMockBuilder(Magic_Admin::class)->getMock();

        $reflection = new ReflectionClass(Magic_Admin::class);
        $reflection_property = $reflection->getProperty('magic_options');
        $reflection_property->setAccessible(true);

        $reflection_property->setValue($ma, $this->options);

        $this->expectOutputRegex('/.*/');

        $ma->user_role_callback();
    }

    public function test_admin_login_callback()
    {
        WP_Mock::userFunction('checked', [
            'return_in_order' => [' checked', '']
        ]);

        $ma = $this->getMockBuilder(Magic_Admin::class)->getMock();

        $reflection = new ReflectionClass(Magic_Admin::class);
        $reflection_property = $reflection->getProperty('magic_options');
        $reflection_property->setAccessible(true);

        $reflection_property->setValue($ma, $this->options);

        $this->expectOutputRegex('/.*/');

        $ma->admin_login_callback();
    }

    public function test_wc_login_callback()
    {
        WP_Mock::userFunction('checked', [
            'return_in_order' => [' checked', '']
        ]);

        $ma = $this->getMockBuilder(Magic_Admin::class)->getMock();

        $reflection = new ReflectionClass(Magic_Admin::class);
        $reflection_property = $reflection->getProperty('magic_options');
        $reflection_property->setAccessible(true);

        $reflection_property->setValue($ma, $this->options);

        $this->expectOutputRegex('/.*/');

        $ma->wc_login_callback();
    }
}
