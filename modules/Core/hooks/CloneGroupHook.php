<?php
declare(strict_types=1);

/**
 * Clone group event listener handler class
 *
 * @package Core\Hooks
 * @author Partydragen
 * @version 2.0.0
 * @license MIT
 */
class CloneGroupHook {

    /**
     * @param array $params
     */
    public static function execute(array $params = []): void {

        // Clone group permissions for custom pages
        $new_group_id = $params['group_id'];
        $permissions = DB::getInstance()->query('SELECT * FROM nl2_custom_pages_permissions WHERE group_id = ?', [$params['cloned_group_id']]);
        if ($permissions->count()) {
            $permissions = $permissions->results();

            $inserts = [];
            foreach ($permissions as $permission) {
                $inserts[] = '(' . $permission->page_id . ',' . $new_group_id . ',' . $permission->view . ')';
            }

            $query = 'INSERT INTO nl2_custom_pages_permissions (page_id, group_id, view) VALUES ';
            $query .= implode(',', $inserts);

            DB::getInstance()->query($query);
        }
    }
}
