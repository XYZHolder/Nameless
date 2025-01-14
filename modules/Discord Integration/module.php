<?php
declare(strict_types=1);

/**
 * TODO: Add description
 */
class Discord_Module extends Module {

    private Language $_language;

    /**
     * @param Language $language
     * @param Pages $pages
     * @param Endpoints $endpoints
     */
    public function __construct(Language $language, Pages $pages, Endpoints $endpoints) {
        $this->_language = $language;

        $name = 'Discord Integration';
        $author = '<a href="https://tadhg.sh" target="_blank" rel="nofollow noopener">Aberdeener</a>';
        $module_version = '2.0.2';
        $nameless_version = '2.0.2';

        parent::__construct($this, $name, $author, $module_version, $nameless_version);

        $bot_url = Util::getSetting('discord_bot_url');
        if ($bot_url === null) {
            $bot_url = '';
        }
        define('BOT_URL', $bot_url);

        $bot_username = Util::getSetting('discord_bot_username');
        if ($bot_username === null) {
            $bot_username = '';
        }
        define('BOT_USERNAME', $bot_username);

        $pages->add($this->getName(), '/panel/discord', 'pages/panel/discord.php');

        $endpoints->loadEndpoints(ROOT_PATH . '/modules/Discord Integration/includes/endpoints');

        GroupSyncManager::getInstance()->registerInjector(new DiscordGroupSyncInjector);

        Integrations::getInstance()->registerIntegration(new DiscordIntegration($language));

        // Hooks
        EventHandler::registerEvent('discordWebhookFormatter',
            'Discord webhook formatter',
            [
                'data' => 'Event data',
                'format' => 'The format which being sent to the discord webhook'
            ],
            true,
            true
        );

        EventHandler::registerListener('discordWebhookFormatter', 'DiscordFormatterHook::format');
    }

    /**
     *
     * @return void
     */
    public function onInstall(): void {
    }

    /**
     *
     * @return void
     */
    public function onUninstall(): void {
    }

    /**
     *
     * @return void
     */
    public function onDisable(): void {
    }

    /**
     *
     * @return void
     */
    public function onEnable(): void {
    }

    /**
     * Handle page loading for this module.
     * Often used to register permissions, sitemaps, widgets, etc.
     *
     * @param User $user User viewing the page.
     * @param Pages $pages Instance of pages class.
     * @param Cache $cache Instance of cache to pass.
     * @param Smarty $smarty Instance of smarty to pass.
     * @param Navigation $navs Array of loaded navigation menus.
     * @param Widgets $widgets Instance of widget class to pass.
     * @param TemplateBase|null $template Active template to render.
     *
     * @throws Exception
     */
    public function onPageLoad(User $user, Pages $pages, Cache $cache, Smarty $smarty, $navs, Widgets $widgets, ?TemplateBase $template): void {
        PermissionHandler::registerPermissions($this->getName(), [
            'admincp.discord' => $this->_language->get('admin', 'integrations') . ' &raquo; ' . Discord::getLanguageTerm('discord'),
        ]);

        if (defined('FRONT_END') || (defined('PANEL_PAGE') && str_contains(PANEL_PAGE, 'widget'))) {
            $widgets->add(new DiscordWidget($cache, $smarty));
        }

        if (!defined('FRONT_END')) {
            $cache->setCacheName('panel_sidebar');

            if ($user->hasPermission('admincp.discord')) {
                if (!$cache->hasCashedData('discord_icon')) {
                    $icon = '<i class="nav-icon fab fa-discord"></i>';
                    $cache->store('discord_icon', $icon);
                } else {
                    $icon = $cache->retrieve('discord_icon');
                }

                $navs[2]->addItemToDropdown('integrations', 'discord', Discord::getLanguageTerm('discord'), URL::build('/panel/discord'), 'top', null, $icon, 1);
            }
        }
    }

    /**
     * Get debug information to display on the external debug link page.
     *
     * @return array<string, string> Debug information for this module.
     */
    public function getDebugInfo(): array {
        return [
            'guild_id' => Discord::getGuildId(),
            'roles' => Discord::getRoles(),
            'bot_setup' => Discord::isBotSetup(),
            'bot_url' => BOT_URL,
        ];
    }
}
