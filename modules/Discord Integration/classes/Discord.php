<?php
/**
 * Discord utility class
 *
 * @package Modules\Discord Integration
 * @author Aberdeener
 * @version 2.0.0-pr13
 * @license MIT
 */
class Discord {

    /**
     * @var bool Whether the Discord bot is set up properly
     */
    private static bool $_is_bot_setup;

    /**
     * @var Language Instance of Language class for translations
     */
    private static Language $_discord_integration_language;

    /**
     * @var array Valid responses from the Discord bot in the root "status" key
     */
    private const VALID_ROOT_STATUSES = [
        'success',
        'bad_request',
        'not_linked',
        'unauthorized',
        'invalid_guild',
    ];

    /**
     * @var array Valid responses from the Discord bot in each of the "role_change->success" keys
     */
    private const VALID_ROLE_CHANGE_STATUSES = [
        'none',
        'added',
        'removed',
        'no_permission',
        'invalid_user',
        'invalid_role',
    ];

    /**
     * Update a user's roles in the Discord guild.
     *
     * @param User $user The user whose roles to update
     * @param array $added Array of Discord role IDs to add
     * @param array $removed Array of Discord role IDs to remove
     * @return bool Whether the request was successful or not
     */
    public static function updateDiscordRoles(User $user, array $added, array $removed): bool {
        if (!self::isBotSetup()) {
            return false;
        }

        $integrationUser = $user->getIntegration('Discord');
        if ($integrationUser === null || !$integrationUser->isVerified()) {
            return false;
        }

        $role_changes = array_merge(
            self::assembleGroupArray($integrationUser->data()->identifier, $added, 'add'),
            self::assembleGroupArray($integrationUser->data()->identifier, $removed, 'remove')
        );

        if (!count($role_changes)) {
            return false;
        }

        $response = self::discordBotRequest('/applyRoleChanges', self::assembleJson($role_changes));

        if ($response['status'] === 'success') {
            // check if all the role_changes status is "success"
            foreach ($response['role_changes'] as $role_change) {
                if (in_array($role_change['status'], ['added', 'removed'])) {
                    return false;
                }
            }

            foreach ($response['role_changes'] as $role_change) {
                if ($role_change['status'] === 'no_permission') {
                    //
                }

                if ($role_change['status'] === 'invalid_user') {
                    //
                }

                if ($role_change['status'] === 'invalid_role') {
                    //
                }

                if (!in_array($role_change['status'], ['added', 'removed', 'none'])) {
                    Log::getInstance()->log(Log::Action('discord/role_set'), self::getLanguageTerm("discord_bot_error_{$response['status']}"), $user->data()->id);
                }

                return false;
            }

            return true;
        } else {

        }

//        if (in_array($response, self::VALID_ROOT_STATUSES)) {
//            return $response;
//        }
//
//        // Log unknown error from bot
//        Log::getInstance()->log(Log::Action('discord/role_set'), $response);
//        return false;
//
//        if ($result == 'fullsuccess') {
//            return true;
//        }

        if ($response === 'partsuccess') {
            Log::getInstance()->log(Log::Action('discord/role_set'), self::getLanguageTerm('discord_bot_error_partsuccess'), $user->data()->id);
            return true;
        }

        $errors = self::parseErrors($response);

        foreach ($errors as $error) {
            Log::getInstance()->log(Log::Action('discord/role_set'), $error, $user->data()->id);
        }

        return false;
    }

    /**
     * @return bool Whether the Discord bot is set up properly
     */
    public static function isBotSetup(): bool {
        return self::$_is_bot_setup ??= Util::getSetting('discord_integration');
    }

    /**
     * Create a JSON object to send to the Discord bot.
     *
     * @param string $user_id The Discord user ID
     * @param array $role_ids Array of Discord role IDs to add or remove
     * @param string $action Whether to 'add' or 'remove' the groups
     * @return array Assembled array of Discord role IDs and their action
     */
    private static function assembleGroupArray(string $user_id, array $role_ids, string $action): array {
        return array_map(static fn($role_id) => [
            'user_id' => $user_id,
            'role_id' => $role_id,
            'action' => $action,
        ], $role_ids);
    }

    /**
     * Get the associated NamelessMC group ID for a Discord role.
     *
     * @param DB $db Instance of DB class
     * @param int $nameless_group_id The ID of the NamelessMC group
     * @return null|int The Discord role ID for the NamelessMC group
     */
    public static function getDiscordRoleId(DB $db, int $nameless_group_id): ?int {
        $nameless_injector = GroupSyncManager::getInstance()->getInjectorByClass(NamelessMCGroupSyncInjector::class);

        $discord_role_id = $db->get('group_sync', [$nameless_injector->getColumnName(), $nameless_group_id]);
        if ($discord_role_id->count()) {
            return $discord_role_id->first()->discord_role_id;
        }

        return null;
    }

    /**
     * Create a JSON objec to send to the Discord bot.
     *
     * @param array $role_changes Array of Discord role IDs to add or remove (compiled with `assembleGroupArray`)
     * @return string JSON object to send to the Discord bot
     */
    private static function assembleJson(array $role_changes): string {
        return json_encode([
            'guild_id' => trim(self::getGuildId()),
            'api_key' => trim(Util::getSetting('mc_api_key')),
            'role_changes' => $role_changes,
        ]);
    }

    /**
     * @return string|null Discord guild ID for this site
     */
    public static function getGuildId(): ?string {
        return Util::getSetting('discord');
    }

    /**
     * Make a request to the Discord bot.
     *
     * @param string $url URL of the Discord bot instance
     * @param string|null $body Body of the request
     * @return false|string Response from the Discord bot or false if the request failed
     */
    private static function discordBotRequest(string $url = '/status', ?string $body = null) {
        $client = HttpClient::post(BOT_URL . $url, $body);

        if ($client->hasError()) {
            Log::getInstance()->log(Log::Action('discord/role_set'), $client->getError());
            return false;
        }

        return $client->contents();
    }

    /**
     * Get a language term for the Discord Integration module.
     *
     * @param string $term Term to search for
     * @param array $variables Variables to replace in the term
     * @return string Language term from the language file
     */
    public static function getLanguageTerm(string $term, array $variables = []): string {
        if (!isset(self::$_discord_integration_language)) {
            self::$_discord_integration_language = new Language(ROOT_PATH . '/modules/Discord Integration/language');
        }

        return self::$_discord_integration_language->get('discord_integration', $term, $variables);
    }

    /**
     * Parse errors from a request to the Discord bot.
     *
     * @param mixed $result Result of the Discord bot request
     * @return array Array of errors during a request to the Discord bot
     */
    private static function parseErrors($result): array {
        if ($result === false) {
            // This happens when the url is invalid OR the bot is unreachable (down, firewall, etc)
            // OR they have `allow_url_fopen` disabled in php.ini OR the bot returned a new error (they should always check logs)
            return [
                self::getLanguageTerm('discord_communication_error'),
                self::getLanguageTerm('discord_bot_check_logs'),
            ];
        }

        if (in_array($result, self::VALID_ROOT_STATUSES)) {
            return [self::getLanguageTerm('discord_bot_error_' . $result)];
        }

        // This should never happen
        return [self::getLanguageTerm('discord_unknown_error')];
    }

    /**
     * Cache Discord roles.
     *
     * @param mixed $roles Discord roles to cache
     */
    public static function saveRoles($roles): void {
        $roles = [json_encode($roles)];
        file_put_contents(ROOT_PATH . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . sha1('discord_roles') . '.cache', $roles);
    }

    /**
     * Get cached Discord roles.
     *
     * @return array Cached Discord roles
     */
    public static function getRoles(): array {
        if (file_exists(ROOT_PATH . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . sha1('discord_roles') . '.cache')) {
            return json_decode(file_get_contents(ROOT_PATH . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . sha1('discord_roles') . '.cache'), true);
        }

        return [];
    }
}
