<?php
declare(strict_types=1);

use GuzzleHttp\Exception\GuzzleException;

/**
 * Online users widget
 *
 * @package Core\Widgets
 * @author Samerton
 * @version 2.0.0-pr8
 * @license MIT
 */
class OnlineUsersWidget extends WidgetBase {
    private Cache $_cache;
    private Language $_language;

    /**
     * @param Cache $cache
     * @param Smarty $smarty
     * @param Language $language
     */
    public function __construct(Cache $cache, Smarty $smarty, Language $language) {
        $this->_smarty = $smarty;
        $this->_cache = $cache;
        $this->_language = $language;

        // Get widget
        $widget_query = self::getData('Online Users');

        parent::__construct(self::parsePages($widget_query));

        // Set widget variables
        $this->_module = 'Core';
        $this->_name = 'Online Users';
        $this->_location = $widget_query->location;
        $this->_description = 'Displays a list of online users on your website.';
        $this->_settings = ROOT_PATH . '/modules/Core/includes/admin_widgets/online_users.php';
        $this->_order = $widget_query->order;
    }

    /**
     * Generate this widget's `$_content`.
     *
     * @throws GuzzleException
     * @throws SmartyException
     */
    public function initialise(): void {
        $this->_cache->setCacheName('online_members');

        if ($this->_cache->hasCashedData('users')) {
            $online = $this->_cache->retrieve('users');
            $use_nickname_show = $this->_cache->retrieve('show_nickname_instead');
        } else {
            if ($this->_cache->hasCashedData('include_staff_in_users')) {
                $include_staff = $this->_cache->retrieve('include_staff_in_users');
            } else {
                $include_staff = 0;
                $this->_cache->store('include_staff_in_users', 0);
            }
            if ($this->_cache->hasCashedData('show_nickname_instead')) {
                $use_nickname_show = $this->_cache->retrieve('show_nickname_instead');
            } else {
                $use_nickname_show = 0;
                $this->_cache->store('show_nickname_instead', 0);
            }

            if ($include_staff) {
                $online = DB::getInstance()->query('SELECT id FROM nl2_users WHERE last_online > ?', [strtotime('-5 minutes')])->results();
            } else {
                $online = DB::getInstance()->query('SELECT U.id FROM nl2_users AS U JOIN nl2_users_groups AS UG ON (U.id = UG.user_id) JOIN nl2_groups AS G ON (UG.group_id = G.id) WHERE G.order = (SELECT min(iG.`order`) FROM nl2_users_groups AS iUG JOIN nl2_groups AS iG ON (iUG.group_id = iG.id) WHERE iUG.user_id = U.id GROUP BY iUG.user_id ORDER BY NULL) AND U.last_online > ' . strtotime('-5 minutes') . ' AND G.staff = 0')->results();
            }

            $this->_cache->store('users', $online, 120);
        }

        // Generate HTML code for widget
        if (count($online)) {
            $users = [];

            foreach ($online as $item) {
                $online_user = new User($item->id);
                if ($online_user->exists()) {
                    $users[] = [
                        'profile' => $online_user->getProfileURL(),
                        'style' => $online_user->getGroupStyle(),
                        'username' => $online_user->getDisplayName(true),
                        'nickname' => $online_user->getDisplayName(),
                        'avatar' => $online_user->getAvatar(),
                        'id' => Output::getClean((string)$online_user->data()->id),
                        'title' => Output::getClean($online_user->data()->user_title),
                        'group' => $online_user->getMainGroup()->group_html
                    ];
                }
            }

            $this->_smarty->assign([
                'SHOW_NICKNAME_INSTEAD' => $use_nickname_show,
                'ONLINE_USERS' => $this->_language->get('general', 'online_users'),
                'ONLINE_USERS_LIST' => $users,
                'TOTAL_ONLINE_USERS' => $this->_language->get('general', 'total_online_users', ['count' => count($users)])
            ]);

        } else {
            $this->_smarty->assign([
                'ONLINE_USERS' => $this->_language->get('general', 'online_users'),
                'NO_USERS_ONLINE' => $this->_language->get('general', 'no_online_users'),
                'TOTAL_ONLINE_USERS' => $this->_language->get('general', 'total_online_users', ['count' => 0])
            ]);
        }

        $this->_content = $this->_smarty->fetch('widgets/online_users.tpl');
    }
}
