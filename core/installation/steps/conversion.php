<?php
declare(strict_types=1);
/**
 *  Made by Samerton
 *  https://github.com/NamelessMC/Nameless/
 *  NamelessMC version 2.1.0
 *
 *  License: MIT
 *
 *  TODO: Description
 * @var Language $language
 */

if (!isset($_SESSION['admin_setup']) || !$_SESSION['admin_setup']) {
    Redirect::to('?step=admin_account_setup');
}
?>

<div class="ui segments">
    <div class="ui secondary segment">
        <h4 class="ui header">
            <?php
            echo $language->get('installer', 'convert'); ?>
        </h4>
    </div>
    <div class="ui segment">
        <?php
        echo $language->get('installer', 'convert_message'); ?>
    </div>
    <div class="ui right aligned secondary segment">
        <a class="ui small button" href="?step=conversion_perform">
            <?php
            echo $language->get('installer', 'yes'); ?>
        </a>
        <a class="ui small primary button" href="?step=finish">
            <?php
            echo $language->get('installer', 'no'); ?>
        </a>
    </div>
</div>
