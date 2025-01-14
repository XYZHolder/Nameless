<?php
declare(strict_types=1);

use DebugBar\DataCollector\ConfigCollector;
use DebugBar\DataCollector\MemoryCollector;
use DebugBar\DataCollector\PDO\PDOCollector;
use DebugBar\DataCollector\PhpInfoCollector;
use DebugBar\DataCollector\RequestDataCollector;
use DebugBar\DataCollector\TimeDataCollector;
use DebugBar\DebugBar;
use DebugBar\DebugBarException;
use Junker\DebugBar\Bridge\SmartyCollector;

/**
 * Class to help integrate the PHPDebugBar with NamelessMC.
 *
 * @package NamelessMC\Misc
 * @author Aberdeener
 * @version 2.0.0-pr13
 * @license MIT
 */
class DebugBarHelper extends Instanceable {

    private ?DebugBar $_debugBar = null;

    /**
     * Enable the PHPDebugBar + add the PDO Collector
     * @throws DebugBarException
     */
    public function enable(Smarty $smarty): void {
        $debugbar = new DebugBar();

        $debugbar->addCollector(new TimeDataCollector());
        $debugbar->addCollector(new RequestDataCollector());

        $configCollector = new ConfigCollector();
        $configCollector->setData(array_filter(Config::all(), static function ($key) {
            return $key !== 'mysql' && $key !== 'email';
        }, ARRAY_FILTER_USE_KEY));
        $debugbar->addCollector($configCollector);

        $pdoCollector = new PDOCollector(DB::getInstance()->getPDO());
        $pdoCollector->setRenderSqlWithParams(true, '`');
        $debugbar->addCollector($pdoCollector);

        $debugbar->addCollector(new SmartyCollector($smarty));
        $debugbar->addCollector(new PhpInfoCollector());
        $debugbar->addCollector(new MemoryCollector());

        $this->_debugBar = $debugbar;
    }

    /**
     *
     * @return DebugBar|null
     */
    public function getDebugBar(): ?DebugBar {
        return $this->_debugBar;
    }

}
