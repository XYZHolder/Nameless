<?php
declare(strict_types=1);

/**
 * Provides simpler abstractions over common database queries.
 *
 * @package NamelessMC\Database
 * @author Samerton
 * @version 2.0.0-pr13
 * @license MIT
 * @deprecated Use DB class. Will be removed in 2.1.0
 */
class Queries {
    /**
     * @var ?DB $_db
     */
    private static ?DB $_db;

    public function __construct() {
        if (!isset(self::$_db)) {
            self::$_db = DB::getInstance();
        }
    }

    /**
     * @deprecated Use DB::getInstance()->get(...)->results() with identical parameters instead. Will be removed in 2.1.0
     *
     * find and replace
     * from: \$queries->getWhere\((.*)\)
     * to:   DB::getInstance()->get($1)->results()
     */
    public function getWhere(string $table, array $where): array {
        $this->warnDeprecated();
        return self::$_db->get($table, $where)->results();
    }

    /**
     *
     * @return void
     */
    private function warnDeprecated(): void {
        $caller = debug_backtrace()[1];
        ErrorHandler::logWarning('Deprecated Queries class used in ' . $caller['file'] . ' line ' . $caller['line'] . '. Please report this issue.');
    }

    /**
     * @deprecated Use DB::getInstance()->orderAll(...)->results() with identical parameters instead. Will be removed in 2.1.0
     *
     * find and replace
     * from: \$queries->orderAll\((.*)\)
     * to:   DB::getInstance()->orderAll($1)->results()
     */
    public function orderAll(string $table, string $order, string $sort = null): array {
        $this->warnDeprecated();
        return self::$_db->orderAll($table, $order, $sort)->results();
    }

    /**
     * @deprecated Use DB::getInstance()->orderWhere(...)->results() with identical parameters instead.  Will be removed in 2.1.0
     *
     * find and replace
     * from: \$queries->orderWhere\((.*)\)
     * to:   DB::getInstance()->orderWhere($1)->results()
     */
    public function orderWhere(string $table, string $where, string $order, string $sort = null): array {
        $this->warnDeprecated();
        return self::$_db->orderWhere($table, $where, $order, $sort)->results();
    }

    /**
     * @deprecated Use DB::getInstance()->insert(...) with identical parameters instead. Will be removed in 2.1.0
     *
     * find and replace
     * from: \$queries->create\((.*)\)
     * to:   DB::getInstance()->insert($1)
     */
    public function create(string $table, array $fields = []): void {
        $this->warnDeprecated();
        if (!self::$_db->insert($table, $fields)) {
            throw new RuntimeException('There was a problem performing that action.');
        }
    }

    /**
     * @deprecated Use DB::getInstance()->delete(...) with identical parameters instead. Will be removed in 2.1.0
     *
     * find and replace
     * from: \$queries->delete\((.*)\)
     * to:   DB::getInstance()->delete($1)
     */
    public function delete(string $table, array $where): void {
        $this->warnDeprecated();
        if (!self::$_db->delete($table, $where)) {
            throw new RuntimeException('There was a problem performing that action.');
        }
    }

    /**
     * @deprecated Use DB::getInstance()->increment(...) with identical parameters instead. Will be removed in 2.1.0
     *
     * find and replace
     * from: \$queries->increment\((.*)\)
     * to:   DB::getInstance()->increment($1)
     */
    public function increment(string $table, string $id, string $field): void {
        $this->warnDeprecated();
        if (!self::$_db->increment($table, $id, $field)) {
            throw new RuntimeException('There was a problem performing that action.');
        }
    }

    /**
     * @deprecated Use DB::getInstance()->createTable(...) with identical parameters instead. Will be removed in 2.1.0
     *
     * find and replace
     * from: \$queries->createTable\((.*)\)
     * to:   DB::getInstance()->createTable($1)
     */
    public function createTable(string $table, string $columns): void {
        $this->warnDeprecated();
        if (!self::$_db->createTable($table, $columns)) {
            throw new RuntimeException('There was a problem performing that action.');
        }
    }

    /**
     * Get the last inserted ID
     *
     * @return string|false
     * @deprecated Use DB::getInstance()->lastId() instead. Will be removed in 2.1.0
     * find and replace
     * from: \$queries->getLastId\(\)
     * to:   DB::getInstance()->lastId()
     *
     */
    public function getLastId() {
        $this->warnDeprecated();
        return self::$_db->lastId();
    }

    /**
     * @deprecated Use DB::getInstance()->addColumn() with identical parameters instead. Will be removed in 2.1.0
     *
     * find and replace
     * from: \$queries->addColumn\((.*)\)
     * to:   DB::getInstance()->addColumn($1)
     */
    public function addColumn(string $table, string $column, string $attributes): void {
        $this->warnDeprecated();
        if (!self::$_db->addColumn($table, $column, $attributes)) {
            throw new RuntimeException('There was a problem performing that action.');
        }
    }

    /**
     * @deprecated Use DB::getInstance()->showTables() with identical parameters instead. Will be removed in 2.1.0
     *
     * find and replace
     * from: \$queries->tableExists\((.*)\)
     * to:   DB::getInstance()->showTables($1)
     */
    public function tableExists(string $table) {
        $this->warnDeprecated();
        return self::$_db->showTables($table);
    }

    /**
     * Used in upgrade scripts to add permissions to a group. For example, if a new staffcp page has been added.
     *
     * @param string $group_id
     * @param string $permission
     *
     * @return void
     */
    public function addPermissionGroup(string $group_id, string $permission): void {
        $group = Group::find($group_id);
        if ($group) {
            $permissions = $group->permissions;
            $permissions = json_decode($permissions, true);
            if (is_array($permissions)) {
                $permissions[$permission] = 1;
                $perms_json = json_encode($permissions);
                self::$_db->update('groups', $group_id, ['permissions' => $perms_json]);
            }
        }
    }

    /**
     * @deprecated Use DB::getInstance()->update(...) with identical parameters instead. Will be removed in 2.1.0
     *
     * find and replace
     * from: \$queries->update\((.*)\)
     * to:   DB::getInstance()->update($1)
     */
    public function update(string $table, $where, array $fields = []): void {
        $this->warnDeprecated();
        if (!self::$_db->update($table, $where, $fields)) {
            throw new RuntimeException('There was a problem performing that action.');
        }
    }

}
