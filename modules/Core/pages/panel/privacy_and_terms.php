<?php
declare(strict_types=1);
/**
 *  Made by Samerton
 *  https://github.com/NamelessMC/Nameless/
 *  NamelessMC version 2.0.0-pr9
 *
 *  License: MIT
 *
 *  Panel API page
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
 */

if (!$user->handlePanelPageLoad('admincp.core.terms')) {
    require_once(ROOT_PATH . '/403.php');
    die();
}

const PAGE = 'panel';
const PARENT_PAGE = 'core_configuration';
const PANEL_PAGE = 'privacy_and_terms';
$page_title = $language->get('admin', 'privacy_and_terms');
require_once(ROOT_PATH . '/core/templates/backend_init.php');

if (Input::exists()) {
    $errors = [];

    try {
        if (Token::check()) {
            try {
                $validation = Validate::check($_POST, [
                    'privacy' => [
                        Validate::REQUIRED => true,
                        Validate::MAX => 100000
                    ],
                    'terms' => [
                        Validate::REQUIRED => true,
                        Validate::MAX => 100000
                    ]
                ])->messages([
                    'privacy' => $language->get('admin', 'privacy_policy_error'),
                    'terms' => $language->get('admin', 'terms_error')
                ]);
            } catch (Exception $ignored) {
            }

            if ($validation->passed()) {
                try {
                    $privacy_id = DB::getInstance()->get('privacy_terms', ['name', 'privacy'])->results();
                    if (count($privacy_id)) {
                        $privacy_id = $privacy_id[0]->id;

                        DB::getInstance()->update('privacy_terms', $privacy_id, [
                            'value' => Input::get('privacy')
                        ]);
                    } else {
                        DB::getInstance()->insert('privacy_terms', [
                            'name' => 'privacy',
                            'value' => Input::get('privacy')
                        ]);
                    }

                    $terms_id = DB::getInstance()->get('privacy_terms', ['name', 'terms'])->results();
                    if (count($terms_id)) {
                        $terms_id = $terms_id[0]->id;

                        DB::getInstance()->update('privacy_terms', $terms_id, [
                            'value' => Input::get('terms')
                        ]);
                    } else {
                        DB::getInstance()->insert('privacy_terms', [
                            'name' => 'terms',
                            'value' => Input::get('terms')
                        ]);
                    }

                    $success = $language->get('admin', 'terms_updated');
                } catch (Exception $e) {
                    $errors[] = $e->getMessage();
                }
            } else {
                $errors = $validation->errors();
            }
        } else {
            $errors[] = $language->get('general', 'invalid_token');
        }
    } catch (Exception $ignored) {
    }
}

// Load modules + template
Module::loadPageWithMessages($user, $pages, $cache, $smarty, [$navigation, $cc_nav, $staffcp_nav], $widgets, $template, $language, $success ?? null, $errors ?? null);

// Get privacy policy + terms
$site_terms = DB::getInstance()->get('privacy_terms', ['name', 'terms'])->results();
if (!count($site_terms)) {
    $site_terms = DB::getInstance()->get('settings', ['name', 't_and_c_site'])->results();
}
$site_terms = $site_terms[0]->value;

$site_privacy = DB::getInstance()->get('privacy_terms', ['name', 'privacy'])->results();
if (!count($site_privacy)) {
    $site_privacy = DB::getInstance()->get('settings', ['name', 'privacy_policy'])->results();
}
$site_privacy = $site_privacy[0]->value;

$smarty->assign([
    'PARENT_PAGE' => PARENT_PAGE,
    'DASHBOARD' => $language->get('admin', 'dashboard'),
    'CONFIGURATION' => $language->get('admin', 'configuration'),
    'PRIVACY_AND_TERMS' => $language->get('admin', 'privacy_and_terms'),
    'PAGE' => PANEL_PAGE,
    'TOKEN' => Token::get(),
    'SUBMIT' => $language->get('general', 'submit'),
    'PRIVACY_POLICY' => $language->get('general', 'privacy_policy'),
    'PRIVACY_POLICY_VALUE' => Output::getPurified((string)$site_privacy),
    'TERMS_AND_CONDITIONS' => $language->get('user', 'terms_and_conditions'),
    'TERMS_AND_CONDITIONS_VALUE' => Output::getPurified((string)$site_terms)
]);

$template->onPageLoad();

require(ROOT_PATH . '/core/templates/panel_navbar.php');

// Display template
try {
    $template->displayTemplate('core/privacy_and_terms.tpl', $smarty);
} catch (SmartyException $ignored) {
}
