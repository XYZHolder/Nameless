<?php
declare(strict_types=1);
/**
 *  Made by Partydragen
 *  https://github.com/NamelessMC/Nameless/
 *  NamelessMC version 2.1.0
 *
 *  License: MIT
 *
 *  Latest posts widget settings
 *
 * @var Language $language
 * @var Smarty $smarty
 * @var Language $forum_language
 */

if (Input::exists()) {
    $errors = [];

    try {
        if (Token::check(Input::get('token'))) {
            if (isset($_POST['limit']) && $_POST['limit'] > 0) {
                Util::setSetting('latest_posts_limit', Input::get('limit'), 'Forum');
            }

            $success = $language->get('admin', 'widget_updated');
        } else {
            $errors[] = $language->get('general', 'invalid_token');
        }
    } catch (Exception $ignored) {
    }
}

$smarty->assign([
    'LATEST_POSTS_LIMIT' => $forum_language->get('forum', 'latest_posts_limit'),
    'LATEST_POSTS_LIMIT_VALUE' => Util::getSetting('latest_posts_limit', '5', 'Forum'),
    'INFO' => $language->get('general', 'info'),
    'WIDGET_CACHED' => $forum_language->get('forum', 'latest_posts_widget_cached'),
    'SETTINGS_TEMPLATE' => 'forum/widgets/latest_posts.tpl'
]);
