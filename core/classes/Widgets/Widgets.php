<?php
declare(strict_types=1);

/**
 * Widget management class
 *
 * @package NamelessMC\Misc
 * @author Samerton
 * @version 2.0.0-pr8
 * @license MIT
 */
class Widgets {

    /**
     * @var ?DB $_db
     */
    private static ?DB $_db;
    private Cache $_cache;
    private Language $_language;
    private Smarty $_smarty;

    private array $_widgets = [];
    private array $_enabled = [];
    private string $_name;

    /**
     * @param Cache $cache
     * @param Language $language
     * @param Smarty $smarty
     * @param string $name
     */
    public function __construct(
        Cache    $cache,
        Language $language,
        Smarty   $smarty,
        string   $name = 'core'
    ) {
        // Assign name to use in cache file
        $this->_name = $name;
        $this->_cache = $cache;
        $this->_cache->setCacheName($this->_name . '-widgets');

        if (!isset(self::$_db)) {
            self::$_db = DB::getInstance();
        }
        $this->_language = $language;
        $this->_smarty = $smarty;

        $enabled = $this->_cache->retrieve('enabled');
        if ($enabled !== null && count($enabled)) {
            $this->_enabled = $enabled;
        }
    }

    /**
     * Register a widget to the widget list.
     *
     * @param WidgetBase $widget Instance of widget to register.
     */
    public function add(WidgetBase $widget): void {
        $this->_widgets[$widget->getName()] = $widget;
    }

    /**
     * Get the name of this collection of widgets.
     * Not used internally.
     *
     * @return string Name of this instance.
     */
    public function getName(): string {
        return $this->_name;
    }

    /**
     * Enable a widget.
     *
     * @param WidgetBase $widget Instance of widget to enable.
     */
    public function enable(WidgetBase $widget): void {
        // Add widget to enabled widget list
        $this->_enabled[$widget->getName()] = true;
        $this->_cache->setCacheName($this->_name . '-widgets');
        $this->_cache->store('enabled', $this->_enabled);

        // Update database
        $widget_id = self::$_db->get('widgets', ['name', $widget->getName()]);
        if ($widget_id->count()) {
            $widget_id = $widget_id->first();
            self::$_db->update('widgets', $widget_id->id, [
                'enabled' => true
            ]);
        }
    }

    /**
     * Disable a widget.
     *
     * @param WidgetBase $widget Instance of widget to disable.
     */
    public function disable(WidgetBase $widget): void {
        unset($this->_enabled[$widget->getName()]);
        $this->_cache->setCacheName($this->_name . '-widgets');
        $this->_cache->store('enabled', $this->_enabled);

        // Update database
        $widget_id = self::$_db->get('widgets', ['name', $widget->getName()]);
        if ($widget_id->count()) {
            $widget_id = $widget_id->first();
            self::$_db->update('widgets', $widget_id->id, [
                'enabled' => false,
            ]);
        }
    }

    /**
     * Get a widget by name.
     *
     * @param string $name Name of widget to get.
     *
     * @return WidgetBase|null Instance of widget with same name, null if it doesn't exist.
     */
    public function getWidget(string $name): ?WidgetBase {
        return $this->_widgets[$name] ?? null;
    }

    /**
     * Get code for all enabled widgets on the current page.
     *
     * @param string $location Either `left` or `right`.
     *
     * @return array List of HTML to be displayed.
     * @throws SmartyException
     */
    public function getWidgets(string $location = 'right'): array {
        $ret = [];

        $widgets = $this->getAll();

        foreach ($widgets as $item) {
            if (array_key_exists($item->getName(), $this->_enabled)
                && $item->getLocation() === $location
                && ((defined('CUSTOM_PAGE') && in_array(CUSTOM_PAGE, $item->getPages(), true))
                    || in_array((defined('PAGE') ? PAGE : 'index'), $item->getPages(), true))
            ) {
                try {
                    $item->initialise();
                    $ret[] = $item->display();
                } catch (Exception $e) {
                    ErrorHandler::logWarning('Unable to load widget ' . $item->getName() . ': ' . $e->getMessage());
                    $this->_smarty->assign([
                        'WIDGET_ERROR_TITLE' => $this->_language->get('general', 'unable_to_load_widget'),
                        'WIDGET_ERROR_CONTENT' =>
                            $this->_language->get(
                                'general',
                                'problem_loading_widget',
                                ['widget' => Output::getClean($item->getName())]
                            ),
                        'WIDGET_ERROR_MESSAGE' => $e->getMessage(),
                        'WIDGET_NAME' => Output::getClean($item->getName()),
                    ]);
                    $ret[] = $this->_smarty->fetch('widgets/widget_error.tpl');
                }
            }
        }

        return $ret;
    }

    /**
     * List all widgets, sorted by their order.
     *
     * @return WidgetBase[] List of widgets.
     */
    public function getAll(): iterable {
        $widgets = $this->_widgets;

        uasort($widgets, static function ($a, $b) {
            return $a->getOrder() - $b->getOrder();
        });

        return $widgets;
    }

    /**
     * Get all enabled widget names.
     * Not used internally.
     *
     * @return array List of enabled widget names.
     */
    public function getAllEnabledNames(): array {
        return array_keys($this->_enabled);
    }

    /**
     * Check if widget is enabled or not.
     *
     * @param WidgetBase $widget Instance of widget to check.
     *
     * @return bool Whether this widget is enabled or not.
     */
    public function isEnabled(WidgetBase $widget): bool {
        return array_key_exists($widget->getName(), $this->_enabled);
    }
}
