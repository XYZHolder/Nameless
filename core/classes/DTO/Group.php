<?php
declare(strict_types=1);

/**
 * TODO: Document this file.
 *
 * @package NamelessMC\DTO
 * @author Unknown
 * @version 2.1.0
 * @license MIT
 */
class Group {

    public string $id;
    public string $name;
    public string $group_html;
    public ?string $group_username_color;
    public ?string $group_username_css;
    public bool $admin_cp;
    public bool $staff;
    public ?string $permissions;
    public bool $default_group;
    public int $order;
    public bool $force_tfa;
    public bool $deleted;

    /**
     * @param object $row
     */
    public function __construct(object $row) {
        $this->id = $row->id;
        $this->name = $row->name;
        $this->group_html = $row->group_html;
        $this->group_username_color = $row->group_username_color;
        $this->group_username_css = $row->group_username_css;
        $this->admin_cp = $row->admin_cp;
        $this->staff = $row->staff;
        $this->permissions = $row->permissions;
        $this->default_group = $row->default_group;
        $this->order = $row->order;
        $this->force_tfa = $row->force_tfa;
        $this->deleted = $row->deleted;
    }

    /**
     *
     * @return array<int, Group>
     */
    public static function all(): array {
        $rows = DB::getInstance()->query('SELECT * FROM nl2_groups ORDER BY `order`')->results();
        $fields = [];
        foreach ($rows as $row) {
            $fields[$row->id] = new Group($row);
        }
        return $fields;
    }

    /**
     * @param string $value
     * @param string $column
     *
     * @return Group|null
     */
    public static function find(string $value, string $column = 'id'): ?Group {
        $row = DB::getInstance()->get('groups', [$column, $value])->first();
        return $row
            ? new Group($row)
            : null;
    }

}
