<?php
/**
 * Made by UNKNOWN
 * https://github.com/NamelessMC/Nameless/
 * NamelessMC version UNKNOWN
 *
 * License: MIT
 *
 * TODO: Add description
 *
 * @var User $user
 */

if (!$user->isLoggedIn()) {
    die(json_encode(['value' => 0]));
}

$alerts = Alert::getAlerts($user->data()->id);

echo json_encode(['value' => count($alerts), 'alerts' => $alerts]);
