<?php
declare(strict_types=1);
/**
 *  Made by Samerton
 *  https://github.com/NamelessMC/Nameless/
 *  NamelessMC version 2.0.0-pr8
 *
 *  License: MIT
 *
 *  Site terms page
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
 */

// Always define page name
const PAGE = 'terms';
$page_title = $language->get('user', 'terms_and_conditions');
require_once(ROOT_PATH . '/core/templates/frontend_init.php');

// Retrieve terms from database
$site_terms = DB::getInstance()->get('privacy_terms', ['name', 'terms'])->results();
if (!count($site_terms)) {
    $site_terms = DB::getInstance()->get('settings', ['name', 't_and_c_site'])->results();
}
$site_terms = Output::getPurified($site_terms[0]->value);

$nameless_terms = DB::getInstance()->get('settings', ['name', 't_and_c'])->results();
$nameless_terms = Output::getPurified($nameless_terms[0]->value);

$smarty->assign([
    'TERMS' => $language->get('user', 'terms_and_conditions'),
    'SITE_TERMS' => $site_terms,
    'NAMELESS_TERMS' => $nameless_terms
]);

// Load modules + template
Module::loadPage($user, $pages, $cache, $smarty, [$navigation, $cc_nav, $staffcp_nav], $widgets, $template);

$template->onPageLoad();

require(ROOT_PATH . '/core/templates/navbar.php');
require(ROOT_PATH . '/core/templates/footer.php');

// Display template
try {
    $template->displayTemplate('terms.tpl', $smarty);
} catch (SmartyException $ignored) {
}
