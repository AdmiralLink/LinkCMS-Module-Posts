<?php

namespace LinkCMS\Modules\Posts;

use LinkCMS\Controller\Content as ContentController;
use LinkCMS\Modules\Posts\Model\Post;

class Controller extends ContentController {
    static $type = 'post';

    public static function load_all($offset=false, $limit=false, $orderBy='id DESC') {
        $results = parent::load_all($offset, $limit, $orderBy);
        if ($results) {
            $posts = [];
            foreach ($results as $content) {
                $page = new Post($content);
                array_push($posts, $page);
            }
            return $posts;
        }
        return $results;
    }
}