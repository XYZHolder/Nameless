<?php
declare(strict_types=1);

/**
 *  Made by Samerton
 *  https://github.com/NamelessMC/Nameless/
 *  NamelessMC version 2.0.0-pr8
 *
 *  License: MIT
 *
 *  Recent registrations dashboard collection item
 */

use GuzzleHttp\Exception\GuzzleException;

/**
 * TODO: Add description
 */
class RecentRegistrationsItem extends CollectionItemBase {

    private Smarty $_smarty;
    private Language $_language;
    private Cache $_cache;

    /**
     * @param Smarty $smarty
     * @param Language $language
     * @param Cache $cache
     */
    public function __construct(Smarty $smarty, Language $language, Cache $cache) {
        $cache->setCacheName('dashboard_main_items_collection');
        if ($cache->hasCashedData('recent_registrations')) {
            $from_cache = $cache->retrieve('recent_registrations');
            $order = $from_cache['order'] ?? 2;

            $enabled = $from_cache['enabled'] ?? 1;
        } else {
            $order = 2;
            $enabled = 1;
        }

        parent::__construct($order, $enabled);

        $this->_smarty = $smarty;
        $this->_language = $language;
        $this->_cache = $cache;
    }

    /**
     *
     * @return string
     * @throws SmartyException
     * @throws GuzzleException
     */
    public function getContent(): string {
        // Get recent registrations
        $time_ago = new TimeAgo(TIMEZONE);

        $this->_cache->setCacheName('dashboard_main_items_collection');

        if ($this->_cache->hasCashedData('recent_registrations_data')) {
            $data = $this->_cache->retrieve('recent_registrations_data');
        } else {
            $query = DB::getInstance()->orderAll('users', 'joined', 'DESC LIMIT 5')->results();
            $data = [];

            if (count($query)) {
                $i = 0;

                foreach ($query as $item) {
                    $target_user = new User($item->id);
                    $data[] = [
                        'url' => URL::build('/panel/user/' . urlencode($item->id) . '-' . urlencode($item->username)),
                        'username' => $target_user->getDisplayName(true),
                        'nickname' => $target_user->getDisplayName(),
                        'style' => $target_user->getGroupStyle(),
                        'avatar' => $target_user->getAvatar(),
                        'groups' => $target_user->getAllGroupHtml(),
                        'time' => $time_ago->inWords($item->joined, $this->_language),
                        'time_full' => date(DATE_FORMAT, $item->joined),
                    ];

                    if (++$i === 5) {
                        break;
                    }
                }
            }

            $this->_cache->store('recent_registrations_data', $data, 60);
        }

        $this->_smarty->assign([
            'RECENT_REGISTRATIONS' => $this->_language->get('moderator', 'recent_registrations'),
            'REGISTRATIONS' => $data,
            'REGISTERED' => $this->_language->get('user', 'registered'),
            'VIEW' => $this->_language->get('general', 'view')
        ]);

        return $this->_smarty->fetch('collections/dashboard_items/recent_registrations.tpl');
    }

    /**
     *
     * @return float
     */
    public function getWidth(): float {
        return 0.33; // 1/3 width
    }
}
