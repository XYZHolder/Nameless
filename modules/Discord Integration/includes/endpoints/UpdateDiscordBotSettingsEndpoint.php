<?php
declare(strict_types=1);

/**
 * @package Modules\Discord Integration
 * @author Unknown
 * @version 2.1.0
 * @license MIT
 */
class UpdateDiscordBotSettingsEndpoint extends KeyAuthEndpoint {

    public function __construct() {
        $this->_route = 'discord/update-bot-settings';
        $this->_module = 'Discord Integration';
        $this->_description = 'Updates the Discord Bot URL and/or Guild ID setting';
        $this->_method = 'POST';
    }

    /**
     * @param Nameless2API $api
     *
     * @return void
     * @throws Exception
     */
    public function execute(Nameless2API $api): void {
        if (isset($_POST['url'])) {
            try {
                Util::setSetting('discord_bot_url', (string)$_POST['url']);
            } catch (Exception $e) {
                $api->throwError(DiscordApiErrors::ERROR_UNABLE_TO_SET_DISCORD_BOT_URL, $e->getMessage(), 500);
            }
        }

        if (isset($_POST['guild_id'])) {
            try {
                Util::setSetting('discord', (string)$_POST['guild_id']);
            } catch (Exception $e) {
                $api->throwError(DiscordApiErrors::ERROR_UNABLE_TO_SET_DISCORD_GUILD_ID, $e->getMessage(), 500);
            }
        }

        if (isset($_POST['bot_username'])) {
            try {
                Util::setSetting('discord_bot_username', (string)$_POST['bot_username']);
            } catch (Exception $e) {
                $api->throwError(DiscordApiErrors::ERROR_UNABLE_TO_SET_DISCORD_BOT_USERNAME, $e->getMessage(), 500);
            }
        }

        // If bot url and username is empty then its setup for the first time
        if (empty(BOT_URL) && empty(BOT_USERNAME)) {
            Util::setSetting('discord_integration', '1');
        }

        if (isset($_POST['bot_user_id'])) {
            Util::setSetting('discord_bot_user_id', (string)$_POST['bot_user_id']);
        }

        $api->returnArray(['message' => Discord::getLanguageTerm('discord_settings_updated')]);
    }
}
