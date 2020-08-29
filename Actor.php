<?php

namespace LinkCMS\Modules\Posts;

use LinkCMS\Actor\Core;
use LinkCMS\Actor\Display;
use LinkCMS\Actor\Route as Router;

class Actor {
    public static function register() {
        $core = Core::load();
        $core->content->add_content_type('post', 'LinkCMS\Modules\Posts\Model\Post');
        Display::add_template_directory(__DIR__. '/templates');
        Router::register_namespace('posts', 'manage');
        Router::add_route(['LinkCMS\Modules\Posts\Route','do_routes']);
        Route::add_assets_directory();
        Core::add_menu_item('posts', 'Posts', '/manage/posts', false, 5);
        self::add_page_templates();
    }

    public static function get_archive_stem() {
        $core = Core::load();
        if (isset($core->theme->archiveStem)) {
            return $core->theme->archiveStem;
        } else {
            return 'posts';
        }
    }

    public static function add_page_templates() {
        $core = Core::load();
        $postTemplates = ['post' => 'Standard post'];
        if (isset($core->theme->info->templates->post)) {
            foreach ($core->theme->info->templates->post as $innerArray) {
                $postTemplates[$innerArray[0]] = $innerArray[1];
            }
        }
        Display::register_global('postTemplates', $postTemplates);
    }
}