<?php
declare(strict_types=1);

/**
 * Seeder class.
 *
 * @package NamelessMC\Seeder
 * @author Tadgh Boyle
 * @version 2.1.0
 * @license MIT
 */
class ForumCategorySeeder extends Seeder {

    /**
     * @var string[]
     */
    public array $tables = [
        'nl2_forums',
    ];

    /**
     * @param DB $db
     * @param \Faker\Generator $faker
     *
     * @return void
     */
    protected function run(DB $db, \Faker\Generator $faker): void {
        $order = 1;
        $this->times(5, static function () use ($db, $faker, &$order) {
            $db->insert('forums', [
                'forum_title' => $faker->words($faker->boolean ? 2 : 3, true),
                'forum_description' => $faker->boolean(75) ? $faker->sentences($faker->boolean ? 3 : 4, true) : null,
                'parent' => 0,
                'forum_order' => $order++,
                'news' => $faker->boolean(20),
                'forum_type' => 'category',
            ]);

            $forum_id = $db->lastId();
            foreach (ForumSubForumSeeder::GROUP_PERMISSIONS as $group => $permissions) {
                $db->insert('forums_permissions', [
                    'forum_id' => $forum_id,
                    'group_id' => $group,
                    'view' => $permissions['view'],
                    'create_topic' => $permissions['create_topic'],
                    'edit_topic' => $permissions['edit_topic'],
                    'create_post' => $permissions['create_post'],
                    'view_other_topics' => $permissions['view_other_topics'],
                    'moderate' => $permissions['moderate'],
                ]);
            }
        });
    }
}
