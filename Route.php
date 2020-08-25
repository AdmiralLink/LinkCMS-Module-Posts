<?php

namespace LinkCMS\Modules\Posts;

use \Flight;

use LinkCMS\Actor\Config;
use LinkCMS\Actor\Display;
use LinkCMS\Actor\Notify;
use LinkCMS\Actor\User;
use LinkCMS\Actor\Route as Router;
use LinkCMS\Controller\Content as ContentController;
use LinkCMS\Modules\Posts\Controller as PostController;
use LinkCMS\Model\User as UserModel;
use LinkCMS\Modules\Posts\Model\Post as Post;

class Route {
    public static function add_assets_directory() {
        Router::register_folder_map(__DIR__ . '/public/js', 'posts/assets/js');
    }

    // TODO: Run cron to publish scheduled posts

    public static function do_routes() {
        Flight::route('GET /@slug', function($slug) {
            $post = PostController::load_by('slug', $slug);
            if ($post) {
                $post = new Post($post);
                if ($post->status !== 'published') {
                    return true;
                }
                Display::load_page('content/' . $post->template . '.twig', ['post' => $post]);
            } else {
                return true;
            }
        });

        Flight::route('GET /manage/posts', function() {
            User::is_authorized(UserModel::USER_LEVEL_AUTHOR);
            $posts = PostController::load_all(false, false, 'pubDate DESC');
            Display::load_page('/posts/manage/index.twig', ['posts'=>$posts]);
        });
        
        Flight::route('GET /manage/posts/create', function() { 
            User::is_authorized(UserModel::USER_LEVEL_AUTHOR);
            Display::load_page('posts/manage/edit.twig', ['post'=>false]);
        });

        Flight::route('POST /api/posts/save', function() {
            if (User::is_authorized(UserModel::USER_LEVEL_AUTHOR)) {
                // TODO: If updating, have to check that the current user is allowed to update that post    
                if (!empty($_POST)) {
                    $post = new Post($_POST);
                    if ($post->id) {
                        $results = ContentController::update($post);
                        if ($results) {
                            new Notify(['type' => 'update'], 'success');
                        } else {
                            new Notify('Database problem', 'error');
                        }
                    } else {
                        $results = ContentController::save($post);
                        if ($results) {
                            new Notify(['type'=>'insert', 'id'=> $results], 'success');
                        } else {
                            new Notify('Database problem', 'error');
                        }
                    }
                }
            }
        });

        Flight::route('GET /manage/posts/edit/@id', function($id) {
            if (User::is_authorized(UserModel::USER_LEVEL_AUTHOR)) {
                $post = new Post(ContentController::load_by('id', $id));
                if ($post) {
                    $post = json_encode($post);
                    Display::load_page('posts/manage/edit.twig', ['post'=>$post, 'id'=>$id]);
                } else {
                    Flight::error('No such post found');
                }
            }
        });

        Flight::route('GET /@slug/preview', function($slug) {
            User::is_authorized(UserModel::USER_LEVEL_AUTHOR);
            $post = PostController::load_by('slug', $slug);
            if ($post) {
                $post = new Post($post);
                $post->publishedContent = $post->draftContent;
                Display::load_page('content/' . $post->template . '.twig', ['post' => $post]);
            } else {
                return true;
            }
        });

        Flight::route('GET /' . Actor::get_archive_stem() . '(/@pageNumber)', function($pageNumber) {
            $pageNumber = ($pageNumber) ? $pageNumber : 1;
            $limit = 15;
            $offset = false;
            if ($pageNumber && $pageNumber > 1) {
                $offset = (intval($pageNumber)-1) * 15;
            }
            $pageCount = ceil(PostController::get_count() / $limit) ;
            $posts = PostController::load_published($offset, $limit);
            Display::load_page('content/post-archive.twig', ['posts'=>$posts, 'pageNumber'=>$pageNumber, 'stem'=>Actor::get_archive_stem(), 'pageCount'=>$pageCount]);
        });

        Flight::route('GET /manage/posts/preview/@id', function($id) {
            User::is_authorized(UserModel::USER_LEVEL_AUTHOR);
            $post = PostController::load_by('id', $id);
            if ($post) {
                $post = new Post($post);
                $post->publishedContent = $post->draftContent;
                Display::load_page('content/' . $post->template . '.twig', ['post' => $post]);
            } else {
                return true;
            }
        });

        Flight::route('GET /manage/posts/delete/@id', function($id) {
            if (User::is_authorized(UserModel::USER_LEVEL_ADMIN)) {
                $postContent = ContentController::load_by('id', $id);
                if ($postContent) {
                    $post = new Post($postContent);
                    Display::load_page('posts/manage/delete.twig', ['post'=>$post]);   
                } else {
                    Flight::error('No such post');
                }
            }
        });

        Flight::route('POST /manage/posts/delete/@id', function($id) {
            if (User::is_authorized(UserModel::USER_LEVEL_ADMIN)) {
                $postContent = ContentController::load_by('id', $id);
                if ($postContent) {
                    ContentController::delete_by('id', $id);
                    $url = Config::get_config('siteUrl');
                    Flight::redirect($url . '/manage/posts');
                } else {
                    Flight::error('No such post');
                }
            }
        });
    }
}