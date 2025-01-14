<?php
declare(strict_types=1);

/**
 * Recent posts dashboard collection item
 *
 * @package Modules\Forum
 * @author Partydragen
 * @version 2.0.0
 * @license MIT
 */
class CloneGroupForumHook {

    /**
     * @param array $params
     *
     * @return void
     */
    public static function execute(array $params = []): void {

        // Clone group permissions for forums
        $new_group_id = $params['group_id'];
        $permissions = DB::getInstance()->query('SELECT * FROM nl2_forums_permissions WHERE group_id = ?', [$params['cloned_group_id']]);
        if ($permissions->count()) {
            $permissions = $permissions->results();

            $inserts = [];
            foreach ($permissions as $permission) {
                $inserts[] = '(' . $new_group_id . ',' . $permission->forum_id . ',' . $permission->view . ',' . $permission->create_topic . ',' . $permission->edit_topic . ',' . $permission->create_post . ',' . $permission->view_other_topics . ',' . $permission->moderate . ')';
            }

            $query = 'INSERT INTO nl2_forums_permissions (group_id, forum_id, view, create_topic, edit_topic, create_post, view_other_topics, moderate) VALUES ';
            $query .= implode(',', $inserts);

            DB::getInstance()->query($query);
        }
    }
}
