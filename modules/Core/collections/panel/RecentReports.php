<?php
declare(strict_types=1);

/**
 *  Made by Samerton
 *  https://github.com/NamelessMC/Nameless/
 *  NamelessMC version 2.0.0-pr9
 *
 *  License: MIT
 *
 *  Recent reports dashboard collection item
 */

use GuzzleHttp\Exception\GuzzleException;

/**
 * TODO: Add description
 */
class RecentReportsItem extends CollectionItemBase {

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
        if ($cache->hasCashedData('recent_reports')) {
            $from_cache = $cache->retrieve('recent_reports');
            $order = $from_cache['order'] ?? 3;

            $enabled = $from_cache['enabled'] ?? 1;
        } else {
            $order = 3;
            $enabled = 1;
        }

        parent::__construct($order, $enabled);

        $this->_smarty = $smarty;
        $this->_language = $language;
        $this->_cache = $cache;
    }

    /**
     * @return string
     * @throws SmartyException
     * @throws GuzzleException
     */
    public function getContent(): string {
        // Get recent reports
        $time_ago = new TimeAgo(TIMEZONE);

        $this->_cache->setCacheName('dashboard_main_items_collection');

        if ($this->_cache->hasCashedData('recent_reports_data')) {
            $data = $this->_cache->retrieve('recent_reports_data');
        } else {
            $query = DB::getInstance()->query('SELECT * FROM nl2_reports WHERE `status` = 0 ORDER BY `date_reported` DESC LIMIT 5');
            $data = [];

            if ($query->count()) {
                $users = [];

                foreach ($query->results() as $item) {
                    if (array_key_exists($item->reporter_id, $users)) {
                        $reporter_user = $users[$item->reporter_id];
                    } else {
                        $reporter_user = new User($item->reporter_id);
                        if (!$reporter_user->exists()) {
                            continue;
                        }
                        $users[$item->reporter_id] = $reporter_user;
                    }

                    if (array_key_exists($item->reported_id, $users)) {
                        $reported_user = $users[$item->reported_id];
                    } else {
                        $reported_user = new User($item->reported_id);
                        if (!$reported_user->exists()) {
                            continue;
                        }
                        $users[$item->reported_id] = $reported_user;
                    }

                    $data[] = [
                        'url' => URL::build('/panel/users/reports/', 'id=' . urlencode($item->id)),
                        'reporter_username' => $reporter_user->getDisplayName(true),
                        'reporter_nickname' => $reporter_user->getDisplayName(),
                        'reporter_style' => $reporter_user->getGroupStyle(),
                        'reporter_avatar' => $reporter_user->getAvatar(),
                        'reporter_profile' => URL::build('/panel/user/' . urlencode((string)$reporter_user->data()->id) . '-' . urlencode($reporter_user->data()->username)),
                        'reported_username' => $reported_user->getDisplayName(true),
                        'reported_nickname' => $reported_user->getDisplayName(),
                        'reported_style' => $reported_user->getGroupStyle(),
                        'reported_avatar' => $reported_user->getAvatar(),
                        'reported_profile' => URL::build('/panel/user/' . urlencode((string)$reported_user->data()->id) . '-' . urlencode($reported_user->data()->username)),
                        'time' => $time_ago->inWords($item->date_reported, $this->_language),
                        'time_full' => date(DATE_FORMAT, strtotime($item->date_reported)),
                        'type' => $item->type,
                        'reason' => Output::getPurified($item->report_reason),
                        'link' => Output::getClean($item->link),
                        'ig_reported_mcname' => ($item->reported_mcname ? urlencode($item->reported_mcname) : ''),
                        'ig_reported_uuid' => ($item->reported_uuid ? urlencode($item->reported_uuid) : '')
                    ];
                }
            }

            $this->_cache->store('recent_reports_data', $data, 60);
        }

        $this->_smarty->assign([
            'RECENT_REPORTS' => $this->_language->get('moderator', 'recent_reports'),
            'REPORTS' => $data,
            'NO_REPORTS' => $this->_language->get('moderator', 'no_open_reports'),
            'CREATED' => $this->_language->get('moderator', 'created'),
            'REPORTED_BY' => $this->_language->get('moderator', 'reported_by'),
            'REASON' => $this->_language->get('moderator', 'reason:'),
            'WEBSITE' => $this->_language->get('moderator', 'website'),
            'INGAME' => $this->_language->get('moderator', 'ingame'),
            'VIEW' => $this->_language->get('general', 'view')
        ]);

        return $this->_smarty->fetch('collections/dashboard_items/recent_reports.tpl');
    }

    /**
     *
     * @return float
     */
    public function getWidth(): float {
        return 0.33; // 1/3 width
    }
}
