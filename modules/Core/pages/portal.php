<?php
/**
 * Made by Samerton
 * https://github.com/NamelessMC/Nameless/
 * NamelessMC version 2.0.0-pr8
 *
 * License: MIT
 *
 * Portal page
 *
 * @var Language $language
 * @var User $user
 * @var Pages $pages
 * @var Smarty $smarty
 * @var Cache $cache
 * @var Navigation $navigation
 * @var Navigation $cc_nav
 * @var Navigation $staffcp_nav
 * @var Widgets $widgets
 * @var TemplateBase $template
 */

// Always define page name
const PAGE = 'portal';
$page_title = $language->get('general', 'home');
require_once(ROOT_PATH . '/core/templates/frontend_init.php');

// Load modules + template
Module::loadPage($user, $pages, $cache, $smarty, [$navigation, $cc_nav, $staffcp_nav], $widgets, $template);

$template->onPageLoad();

require(ROOT_PATH . '/core/templates/navbar.php');
require(ROOT_PATH . '/core/templates/footer.php');

// Display template
$template->displayTemplate('portal.tpl', $smarty);
