<?php
declare(strict_types=1);
/**
 *  Made by Samerton
 *  https://github.com/NamelessMC/Nameless/
 *  NamelessMC version 2.0.0-pr8
 *
 *  License: MIT
 *
 *  View forum page
 *
 * @var User $user
 * @var Language $language
 * @var Announcements $announcements
 * @var Smarty $smarty
 * @var Pages $pages
 * @var Cache $cache
 * @var Navigation $navigation
 * @var array $cc_nav
 * @var array $staffcp_nav
 * @var Widgets $widgets
 * @var TemplateBase $template
 * @var Language $forum_language
 * @var string $custom_usernames
 * @var string $route
 */

// Always define page name
use GuzzleHttp\Exception\GuzzleException;

const PAGE = 'forum';

$forum = new Forum();
$time_ago = new TimeAgo(TIMEZONE);

// Get forum ID
$fid = explode('/', $route);
$fid = $fid[count($fid) - 1];

if (!strlen($fid)) {
    require_once(ROOT_PATH . '/404.php');
    die();
}

$fid = explode('-', $fid);
if (!is_numeric($fid[0])) {
    require_once(ROOT_PATH . '/404.php');
    die();
}
$fid = Output::getClean($fid[0]);

// Get user group ID
$user_groups = $user->getAllGroupIds();

// Does the forum exist, and can the user view it?
$list = $forum->canViewForum($fid, $user_groups);
if (!$list) {
    require_once(ROOT_PATH . '/403.php');
    die();
}

// Get data from the database
$forum_query = DB::getInstance()->get('forums', ['id', $fid])->results();
$forum_query = $forum_query[0];

// Get page
if (isset($_GET['p'])) {
    if (!is_numeric($_GET['p'])) {
        Redirect::to(URL::build('/forum'));
    }

    if ($_GET['p'] === '1') {
        // Avoid bug in pagination class
        Redirect::to(URL::build('/forum/view/' . urlencode($fid) . '-' . $forum->titleToURL($forum_query->forum_title)));
    }
    $p = $_GET['p'];
} else {
    $p = 1;
}

$page_metadata = DB::getInstance()->get('page_descriptions', ['page', '/forum/view'])->results();
if (count($page_metadata)) {

    define('PAGE_DESCRIPTION', str_replace(
        ['{site}', '{forum_title}', '{page}', '{description}'],
        [Output::getClean(SITE_NAME), Output::getClean($forum_query->forum_title), Output::getClean($p), Output::getClean($forum_query->forum_description)],
        $page_metadata[0]->description
    ));

    define('PAGE_KEYWORDS', $page_metadata[0]->tags);
}

$page_title = $forum_language->get('forum', 'forum');
$page_title .= ' - ' . $language->get('general', 'page_x', ['page' => $p]);
require_once(ROOT_PATH . '/core/templates/frontend_init.php');

// Redirect forum?
if ($forum_query->redirect_forum === '1') {
    if (!URL::isExternalURL($forum_query->redirect_url)) {
        Redirect::to(Output::getClean($forum_query->redirect_url));
    }

    $smarty->assign([
        'CONFIRM_REDIRECT' => $forum_language->get('forum', 'forum_redirect_warning', ['url' => Output::getClean($forum_query->redirect_url)]),
        'YES' => $language->get('general', 'yes'),
        'NO' => $language->get('general', 'no'),
        'REDIRECT_URL' => Output::getClean($forum_query->redirect_url),
        'FORUM_INDEX' => URL::build('/forum')
    ]);

    // Load modules + template
    Module::loadPage($user, $pages, $cache, $smarty, [$navigation, $cc_nav, $staffcp_nav], $widgets, $template);

    $template->onPageLoad();

    try {
        $smarty->assign('WIDGETS_LEFT', $widgets->getWidgets('left'));
        $smarty->assign('WIDGETS_RIGHT', $widgets->getWidgets());
    } catch (SmartyException $ignored) {
    }

    require(ROOT_PATH . '/core/templates/navbar.php');
    require(ROOT_PATH . '/core/templates/footer.php');

    // Display template
    try {
        $template->displayTemplate('forum/view_forum_confirm_redirect.tpl', $smarty);
    } catch (SmartyException $ignored) {
    }
} else {
    // Get all topics
    if ($user->isLoggedIn()) {
        $user_id = $user->data()->id;
    } else {
        $user_id = 0;
    }

    if ($forum->canViewOtherTopics($fid, $user_groups)) {
        $topics = DB::getInstance()->query('SELECT * FROM nl2_topics WHERE forum_id = ? AND sticky = 0 AND deleted = 0 ORDER BY topic_reply_date DESC', [$fid])->results();
    } else {
        $topics = DB::getInstance()->query('SELECT * FROM nl2_topics WHERE forum_id = ? AND sticky = 0 AND deleted = 0 AND topic_creator = ? ORDER BY topic_reply_date DESC', [$fid, $user_id])->results();
    }

    // Get sticky topics
    $stickies = DB::getInstance()->query('SELECT * FROM nl2_topics WHERE forum_id = ? AND sticky = 1 AND deleted = 0 ORDER BY topic_reply_date DESC', [$fid])->results();

    // Search bar
    $smarty->assign([
        'SEARCH_URL' => URL::build('/forum/search'),
        'SEARCH' => $language->get('general', 'search'),
        'TOKEN' => Token::get()
    ]);

    // Breadcrumbs and search bar - same for latest discussions view + table view
    $parent_category = DB::getInstance()->get('forums', ['id', $forum_query->parent])->results();
    $breadcrumbs = [0 => [
        'id' => $forum_query->id,
        'forum_title' => Output::getClean($forum_query->forum_title),
        'active' => 1,
        'link' => URL::build('/forum/view/' . urlencode($forum_query->id) . '-' . $forum->titleToURL($forum_query->forum_title))
    ]];
    if (!empty($parent_category) && $parent_category[0]->parent === '0') {
        // Category
        $breadcrumbs[] = [
            'id' => $parent_category[0]->id,
            'forum_title' => Output::getClean($parent_category[0]->forum_title),
            'link' => URL::build('/forum/view/' . urlencode($parent_category[0]->id) . '-' . $forum->titleToURL($parent_category[0]->forum_title))
        ];
    } else if (!empty($parent_category)) {
        // Parent forum, get its category
        $breadcrumbs[] = [
            'id' => $parent_category[0]->id,
            'forum_title' => Output::getClean($parent_category[0]->forum_title),
            'link' => URL::build('/forum/view/' . urlencode($parent_category[0]->id) . '-' . $forum->titleToURL($parent_category[0]->forum_title))
        ];
        $parent = false;
        while ($parent === false) {
            $parent_category = DB::getInstance()->get('forums', ['id', $parent_category[0]->parent])->results();
            $breadcrumbs[] = [
                'id' => $parent_category[0]->id,
                'forum_title' => Output::getClean($parent_category[0]->forum_title),
                'link' => URL::build('/forum/view/' . urlencode($parent_category[0]->id) . '-' . $forum->titleToURL($parent_category[0]->forum_title))
            ];
            if ($parent_category[0]->parent === '0') {
                $parent = true;
            }
        }
    }

    $breadcrumbs[] = [
        'id' => 'index',
        'forum_title' => $forum_language->get('forum', 'forum_index'),
        'link' => URL::build('/forum')
    ];

    $smarty->assign('BREADCRUMBS', array_reverse($breadcrumbs));

    // Server status module
    $smarty->assign('SERVER_STATUS', '');

    // Assignments
    $smarty->assign('FORUM_INDEX_LINK', URL::build('/forum'));

    // Any subforums?
    $subforums = DB::getInstance()->query('SELECT * FROM nl2_forums WHERE parent = ? ORDER BY forum_order ASC', [$forum_query->id])->results();

    $sub_forum_array = [];

    if (count($subforums)) {
        // append subforums to string
        foreach ($subforums as $sub_forum) {
            // Get number of topics
            if ($forum->forumExist($sub_forum->id, $user_groups)) {
                if ($forum->canViewOtherTopics($sub_forum->id, $user_groups)) {
                    $latest_post = DB::getInstance()->query('SELECT * FROM nl2_topics WHERE forum_id = ? AND deleted = 0 ORDER BY topic_reply_date DESC', [$sub_forum->id])->results();
                } else {
                    $latest_post = DB::getInstance()->query('SELECT * FROM nl2_topics WHERE forum_id = ? AND deleted = 0 AND topic_creator = ? ORDER BY topic_reply_date DESC', [$sub_forum->id, $user_id])->results();
                }

                $sub_forum_topics = count($latest_post);
                if (count($latest_post)) {
                    foreach ($latest_post as $item) {
                        if ($item->deleted === '0') {
                            $latest_post = $item;
                            break;
                        }
                    }

                    try {
                        $latest_post_user = new User($latest_post->topic_last_user);
                    } catch (GuzzleException $ignored) {
                    }
                    $latest_post_link = URL::build('/forum/topic/' . urlencode($latest_post->id) . '-' . $forum->titleToURL($latest_post->topic_title));
                    try {
                        $latest_post_avatar = $latest_post_user->getAvatar();
                    } catch (GuzzleException $ignored) {
                    }
                    $latest_post_title = Output::getClean($latest_post->topic_title);
                    $latest_post_user_display_name = $latest_post_user->getDisplayName();
                    $latest_post_user_link = $latest_post_user->getProfileURL();
                    $latest_post_style = $latest_post_user->getGroupStyle();
                    $latest_post_date_time_ago = $time_ago->inWords($latest_post->topic_reply_date, $language);
                    $latest_post_time = date(DATE_FORMAT, $latest_post->topic_reply_date);
                    $latest_post_user_id = Output::getClean($latest_post->topic_last_user);

                    $latest_post = [
                        'link' => $latest_post_link,
                        'title' => $latest_post_title,
                        'last_user_avatar' => $latest_post_avatar,
                        'last_user' => $latest_post_user_display_name,
                        'last_user_style' => $latest_post_style,
                        'last_user_link' => $latest_post_user_link,
                        'timeago' => $latest_post_date_time_ago,
                        'time' => $latest_post_time,
                        'last_user_id' => $latest_post_user_id
                    ];
                } else {
                    $latest_post = [];
                }

                $sub_forum_array[] = [
                    'id' => $sub_forum->id,
                    'title' => Output::getPurified($sub_forum->forum_title),
                    'description' => Output::getPurified($sub_forum->forum_description),
                    'topics' => $sub_forum_topics,
                    'link' => URL::build('/forum/view/' . urlencode($sub_forum->id) . '-' . $forum->titleToURL($sub_forum->forum_title)),
                    'latest_post' => $latest_post,
                    'icon' => Output::getPurified($sub_forum->icon),
                    'redirect' => $sub_forum->redirect_forum
                ];
            }
        }
    }

    // Assign language variables
    $smarty->assign('FORUMS', $forum_language->get('forum', 'forums'));
    $smarty->assign('DISCUSSION', $forum_language->get('forum', 'discussion'));
    $smarty->assign('TOPIC', $forum_language->get('forum', 'topic'));
    $smarty->assign('STATS', $forum_language->get('forum', 'stats'));
    $smarty->assign('LAST_REPLY', $forum_language->get('forum', 'last_reply'));
    $smarty->assign('BY', $forum_language->get('forum', 'by'));
    $smarty->assign('VIEWS', $forum_language->get('forum', 'views'));
    $smarty->assign('POSTS', $forum_language->get('forum', 'posts'));
    $smarty->assign('STATISTICS', $forum_language->get('forum', 'stats'));
    $smarty->assign('OVERVIEW', $forum_language->get('forum', 'overview'));
    $smarty->assign('LATEST_DISCUSSIONS_TITLE', $forum_language->get('forum', 'latest_discussions'));
    $smarty->assign('TOPICS', $forum_language->get('forum', 'topics'));
    $smarty->assign('NO_TOPICS', $forum_language->get('forum', 'no_topics_short'));
    $smarty->assign('SUBFORUMS', $sub_forum_array);
    $smarty->assign('SUBFORUM_LANGUAGE', $forum_language->get('forum', 'subforums'));
    $smarty->assign('FORUM_TITLE', Output::getPurified($forum_query->forum_title));
    $smarty->assign('FORUM_ICON', Output::getPurified($forum_query->icon));
    $smarty->assign('STICKY_TOPICS', $forum_language->get('forum', 'sticky_topics'));

    // Can the user post here?
    if ($user->isLoggedIn() && $forum->canPostTopic($fid, $user_groups)) {
        $smarty->assign('NEW_TOPIC_BUTTON', URL::build('/forum/new/', 'fid=' . urlencode($fid)));
    } else {
        $smarty->assign('NEW_TOPIC_BUTTON', false);
    }

    $smarty->assign('NEW_TOPIC', $forum_language->get('forum', 'new_topic'));

    // Topics
    if (!count($stickies) && !count($topics)) {
        // No topics yet
        $smarty->assign('NO_TOPICS_FULL', $forum_language->get('forum', 'no_topics'));

        if ($user->isLoggedIn() && $forum->canPostTopic($fid, $user_groups)) {
            $smarty->assign('NEW_TOPIC_BUTTON', URL::build('/forum/new/', 'fid=' . urlencode($fid)));
        } else {
            $smarty->assign('NEW_TOPIC_BUTTON', false);
        }

        $no_topics_exist = true;
    } else {
        // Topics/sticky topics exist
        $labels_cache = [];

        $sticky_array = [];
        // Assign sticky threads to smarty variable
        foreach ($stickies as $sticky) {
            // Get number of replies to a topic
            $replies = DB::getInstance()->get('posts', ['topic_id', $sticky->id])->results();
            $replies = count($replies);

            // Is there a label?
            if ($sticky->label !== '0') { // yes
                // Get label
                if ($labels_cache[$sticky->label]) {
                    $label = $labels_cache[$sticky->label];
                } else {
                    $label = DB::getInstance()->get('forums_topic_labels', ['id', $sticky->label])->results();
                    if (count($label)) {
                        $label = $label[0];

                        $label_html = DB::getInstance()->get('forums_labels', ['id', $label->label])->results();
                        if (count($label_html)) {
                            $label_html = Output::getPurified($label_html[0]->html);
                            $label = str_replace('{x}', Output::getClean($label->name), $label_html);
                        } else {
                            $label = '';
                        }
                    } else {
                        $label = '';
                    }

                    $labels_cache[$sticky->label] = $label;
                }
            } else { // no
                $label = '';
            }

            $labels = [];
            if ($sticky->labels) {
                $topic_labels = explode(',', $sticky->labels);

                foreach ($topic_labels as $item) {
                    // Get label
                    if ($labels_cache[$item]) {
                        $labels[] = $labels_cache[$item];
                    } else {
                        $label_query = DB::getInstance()->get('forums_topic_labels', ['id', $item])->results();
                        if (count($label_query)) {
                            $label_query = $label_query[0];

                            $label_html = DB::getInstance()->get('forums_labels', ['id', $label_query->label])->results();
                            if (count($label_html)) {
                                $label_html = Output::getPurified($label_html[0]->html);
                                $label_html = str_replace('{x}', Output::getClean($label_query->name), $label_html);
                                $labels[] = $label_html;
                                $labels_cache[$item] = $label_html;
                            }
                        }
                    }
                }
            }

            try {
                $topic_user = new User($sticky->topic_creator);
                $last_reply_user = new User($sticky->topic_last_user);
            } catch (GuzzleException $e) {
            }

            // Add to array
            try {
                $sticky_array[] = [
                    'topic_title' => Output::getClean($sticky->topic_title),
                    'topic_id' => $sticky->id,
                    'topic_created_rough' => $time_ago->inWords($sticky->topic_date, $language),
                    'topic_created' => date(DATE_FORMAT, $sticky->topic_date),
                    'topic_created_username' => $topic_user->getDisplayName(),
                    'topic_created_mcname' => $topic_user->getDisplayName(true),
                    'topic_created_style' => $topic_user->getGroupStyle(),
                    'topic_created_user_id' => Output::getClean($sticky->topic_creator),
                    'views' => $sticky->topic_views,
                    'locked' => $sticky->locked,
                    'posts' => $replies,
                    'last_reply_avatar' => $last_reply_user->getAvatar(),
                    'last_reply_rough' => $time_ago->inWords($sticky->topic_reply_date, $language),
                    'last_reply' => date(DATE_FORMAT, $sticky->topic_reply_date),
                    'last_reply_username' => $last_reply_user->getDisplayName(),
                    'last_reply_mcname' => $last_reply_user->getDisplayName(true),
                    'last_reply_style' => $last_reply_user->getGroupStyle(),
                    'last_reply_user_id' => Output::getClean($sticky->topic_last_user),
                    'label' => $label,
                    'labels' => $labels,
                    'author_link' => $topic_user->getProfileURL(),
                    'link' => URL::build('/forum/topic/' . urlencode($sticky->id) . '-' . $forum->titleToURL($sticky->topic_title)),
                    'last_reply_link' => $last_reply_user->getProfileURL()
                ];
            } catch (GuzzleException $ignored) {
            }
        }
        // Clear out variables
        $stickies = null;
        $sticky = null;

        // Latest discussions
        // Pagination
        $paginator = new Paginator(
            $template_pagination ?? null,
            $template_pagination_left ?? null,
            $template_pagination_right ?? null
        );
        $results = $paginator->getLimited($topics, 10, $p, count($topics));
        $pagination = $paginator->generate(7, URL::build('/forum/view/' . urlencode($fid) . '-' . $forum->titleToURL($forum_query->forum_title)));

        if (count($topics)) {
            $smarty->assign('PAGINATION', $pagination);
        } else {
            $smarty->assign('PAGINATION', '');
        }

        $template_array = [];
        // Get a list of all topics from the forum, and paginate
        foreach ($results->data as $n_value) {
            // Get number of replies to a topic
            $replies = DB::getInstance()->get('posts', ['topic_id', $n_value->id])->results();
            $replies = count($replies);

            // Is there a label?
            if (n_value->label !== '0') { // yes
                // Get label
                if ($labels_cache[$n_value->label]) {
                    $label = $labels_cache[$n_value->label];
                } else {
                    $label = DB::getInstance()->get('forums_topic_labels', ['id', $n_value->label])->results();
                    if (count($label)) {
                        $label = $label[0];

                        $label_html = DB::getInstance()->get('forums_labels', ['id', $label->label])->results();
                        if (count($label_html)) {
                            $label_html = $label_html[0]->html;
                            $label = str_replace('{x}', Output::getClean($label->name), Output::getPurified($label_html));
                        } else {
                            $label = '';
                        }
                    } else {
                        $label = '';
                    }

                    $labels_cache[$n_value->label] = $label;
                }
            } else { // no
                $label = '';
            }

            $labels = [];
            if ($n_value->labels) {
                if ($labels_cache[$n_value->labels]) {
                    $labels[] = $labels_cache[$n_value->labels];
                } else {
                    $topic_labels = explode(',', $n_value->labels);

                    foreach ($topic_labels as $item) {
                        // Get label
                        $label_query = DB::getInstance()->get('forums_topic_labels', ['id', $item])->results();
                        if (count($label_query)) {
                            $label_query = $label_query[0];

                            $label_html = DB::getInstance()->get('forums_labels', ['id', $label_query->label])->results();
                            if (count($label_html)) {
                                $label_html = $label_html[0]->html;
                                $label_html = str_replace('{x}', Output::getClean($label_query->name), Output::getPurified($label_html));
                                $labels[] = $label_html;
                                $labels_cache[$item] = $label_html;
                            }
                        }
                    }
                }
            }

            try {
                $topic_user = new User($n_value->topic_creator);
                $last_reply_user = new User($n_value->topic_last_user);
            } catch (GuzzleException $ignored) {
            }

            // Add to array
            try {
                $template_array[] = [
                    'topic_title' => Output::getClean($n_value->topic_title),
                    'topic_id' => $n_value->id,
                    'topic_created_rough' => $time_ago->inWords($n_value->topic_date, $language),
                    'topic_created' => date(DATE_FORMAT, $n_value->topic_date),
                    'topic_created_username' => $topic_user->getDisplayName(),
                    'topic_created_mcname' => $topic_user->getDisplayName(true),
                    'topic_created_style' => $topic_user->getGroupStyle(),
                    'topic_created_user_id' => Output::getClean($n_value->topic_creator),
                    'locked' => $n_value->locked,
                    'views' => $n_value->topic_views,
                    'posts' => $replies,
                    'last_reply_avatar' => $last_reply_user->getAvatar(),
                    'last_reply_rough' => $time_ago->inWords($n_value->topic_reply_date, $language),
                    'last_reply' => date(DATE_FORMAT, $n_value->topic_reply_date),
                    'last_reply_username' => $last_reply_user->getDisplayName(),
                    'last_reply_mcname' => $last_reply_user->getDisplayName(true),
                    'last_reply_style' => $last_reply_user->getGroupStyle(),
                    'label' => $label,
                    'labels' => $labels,
                    'author_link' => $topic_user->getProfileURL(),
                    'link' => URL::build('/forum/topic/' . urlencode($n_value->id) . '-' . $forum->titleToURL($n_value->topic_title)),
                    'last_reply_link' => $last_reply_user->getProfileURL(),
                    'last_reply_user_id' => Output::getClean($n_value->topic_last_user)
                ];
            } catch (GuzzleException $ignored) {
            }
        }

        // Assign to Smarty variable
        $smarty->assign('STICKY_DISCUSSIONS', $sticky_array);
        $smarty->assign('LATEST_DISCUSSIONS', $template_array);
    }

    // Load modules + template
    Module::loadPage($user, $pages, $cache, $smarty, [$navigation, $cc_nav, $staffcp_nav], $widgets, $template);

    $template->onPageLoad();

    try {
        $smarty->assign('WIDGETS_LEFT', $widgets->getWidgets('left'));
        $smarty->assign('WIDGETS_RIGHT', $widgets->getWidgets());
    } catch (SmartyException $ignored) {
    }

    require(ROOT_PATH . '/core/templates/navbar.php');
    require(ROOT_PATH . '/core/templates/footer.php');

    // Display template
    if (isset($no_topics_exist)) {
        try {
            $template->displayTemplate('forum/view_forum_no_discussions.tpl', $smarty);
        } catch (SmartyException $ignored) {
        }
    } else {
        try {
            $template->displayTemplate('forum/view_forum.tpl', $smarty);
        } catch (SmartyException $ignored) {
        }
    }
}
