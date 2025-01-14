<?php
declare(strict_types=1);
/**
 *  Made by Samerton
 *  https://github.com/NamelessMC/Nameless/
 *  NamelessMC version 2.0.0-pr12
 *
 *  License: MIT
 *
 *  Login page
 *
 * @var User $user
 * @var Language $language
 * @var Announcements $announcements
 * @var Smarty $smarty
 * @var Pages $pages
 * @var Cache $cache
 * @var Navigation $navigation
 * @var array $cc_nav
 * @var array $staffcp_nav
 * @var Widgets $widgets
 * @var TemplateBase $template
 * @var Language $forum_language
 */

// Set page name variable
use GuzzleHttp\Exception\GuzzleException;
use RobThree\Auth\TwoFactorAuth;

const PAGE = 'login';
$page_title = $language->get('general', 'sign_in');
require_once(ROOT_PATH . '/core/templates/frontend_init.php');

// Ensure user isn't already logged in
if ($user->isLoggedIn()) {
    Redirect::to(URL::build('/'));
}

// Get login method
$login_method = DB::getInstance()->get('settings', ['name', 'login_method'])->results();
$login_method = $login_method[0]->value;

$captcha = CaptchaBase::isCaptchaEnabled('recaptcha_login');

// Deal with input
if (Input::exists()) {
    // Check form token
    try {
        if (Token::check()) {
            // Valid token
            if (!isset($_SESSION['tfa']) && $captcha) {
                $captcha_passed = CaptchaBase::getActiveProvider()->validateToken($_POST);
            } else {
                $captcha_passed = true;
            }

            if ($captcha_passed) {
                if (isset($_SESSION['password'])) {
                    if (isset($_SESSION['username'])) {
                        $_POST['username'] = $_SESSION['username'];
                        unset($_SESSION['username']);
                    } else if (isset($_SESSION['email'])) {
                        $_POST['email'] = $_SESSION['email'];
                        unset($_SESSION['email']);
                    }

                    $_POST['remember'] = $_SESSION['remember'];
                    $_POST['password'] = $_SESSION['password'];

                    unset($_SESSION['remember'], $_SESSION['password'], $_SESSION['tfa']);
                }

                $rate_limit = [5, 60]; // 5 attempts in 60 seconds - TODO allow this to be customised?

                if ($login_method === 'email') {
                    $to_validate = [
                        'email' => [
                            Validate::REQUIRED => true,
                            Validate::IS_BANNED => true,
                            Validate::IS_ACTIVE => true,
                            Validate::RATE_LIMIT => $rate_limit,
                        ],
                        'password' => [
                            Validate::REQUIRED => true
                        ]
                    ];
                } else {
                    $to_validate = [
                        'username' => [
                            Validate::REQUIRED => true,
                            Validate::IS_BANNED => true,
                            Validate::IS_ACTIVE => true,
                            Validate::RATE_LIMIT => $rate_limit,
                        ],
                        'password' => [
                            Validate::REQUIRED => true
                        ]
                    ];
                }

                try {
                    $validation = Validate::check($_POST, $to_validate)->messages([
                        'email' => [
                            Validate::REQUIRED => $language->get('user', 'must_input_email'),
                            Validate::IS_BANNED => $language->get('user', 'account_banned'),
                            Validate::IS_ACTIVE => $language->get('user', 'inactive_account'),
                            Validate::RATE_LIMIT => static fn($meta) => $language->get('general', 'rate_limit', $meta),
                        ],
                        'username' => [
                            Validate::REQUIRED => ($login_method === 'username' ? $language->get('user', 'must_input_username') : $language->get('user', 'must_input_email_or_username')),
                            Validate::IS_BANNED => $language->get('user', 'account_banned'),
                            Validate::IS_ACTIVE => $language->get('user', 'inactive_account'),
                            Validate::RATE_LIMIT => static fn($meta) => $language->get('general', 'rate_limit', $meta),
                        ],
                        'password' => $language->get('user', 'must_input_password')
                    ]);
                } catch (Exception $ignored) {
                }

                // Check if validation passed
                if ($validation->passed()) {
                    if ($login_method === 'email') {
                        $username = Input::get('email');
                        $method_field = 'email';
                    } else {
                        $username = Input::get('username');
                        if (($login_method === 'email_or_username') && str_contains(Input::get('username'), '@')) {
                            $method_field = 'email';
                        } else {
                            $method_field = 'username';
                        }
                    }

                    try {
                        $user_query = new User($username, $method_field);
                    } catch (GuzzleException $ignored) {
                    }

                    if ($user_query->exists()) {
                        if ($user_query->data()->tfa_enabled === true && $user_query->data()->tfa_complete === true) {
                            // Verify password first
                            try {
                                if ($user->checkCredentials($username, Input::get('password'), $method_field)) {
                                    if (!isset($_POST['tfa_code'])) {
                                        if ($user_query->data()->tfa_type === 0) {
                                            // Emails
                                            // TODO

                                        } else {
                                            // App
                                            require(ROOT_PATH . '/core/includes/tfa_signin.php');
                                            die();
                                        }
                                    } else if ($user_query->data()->tfa_type === 1) {
                                        // App
                                        $tfa = new TwoFactorAuth('NamelessMC');

                                        if ($tfa->verifyCode($user_query->data()->tfa_secret, str_replace(' ', '', $_POST['tfa_code'])) !== true) {
                                            Session::flash('tfa_signin', $language->get('user', 'invalid_tfa'));
                                            require(ROOT_PATH . '/core/includes/tfa_signin.php');
                                            die();
                                        }
                                    } else {
                                        // Email
                                        // TODO
                                    }
                                } else {
                                    $return_error = [$language->get('user', 'incorrect_details')];
                                }
                            } catch (GuzzleException $ignored) {
                            }
                        }

                        if (!isset($return_error)) {

                            // Validation passed
                            // Initialise user class
                            $user = new User();

                            // Did the user check 'remember me'?
                            $remember = Input::get('remember') === '1';

                            $cache->setCacheName('authme_cache');
                            $authme_db = $cache->retrieve('authme');

                            if (defined("MINECRAFT") && MINECRAFT === true && $authme_db['sync'] === '1' && Util::getSetting('authme') === '1') {

                                // Sync AuthMe password
                                try {
                                    $authme_conn = new mysqli($authme_db['address'], $authme_db['user'], $authme_db['pass'], $authme_db['db'], $authme_db['port']);

                                    if ($authme_conn->connect_errno) {
                                        // Connection error
                                        // Continue anyway, and use already stored password
                                    } else {
                                        // Success, check user exists in database and validate password
                                        if ($method_field === 'email') {
                                            $field = 'email';
                                        } else {
                                            $field = 'realname';
                                        }

                                        $stmt = $authme_conn->prepare('SELECT password FROM ' . $authme_db['table'] . ' WHERE ' . $field . ' = ?');
                                        if ($stmt) {
                                            $stmt->bind_param('s', $username);
                                            $stmt->execute();
                                            $stmt->bind_result($password);

                                            while ($stmt->fetch()) {
                                                // Retrieve result
                                            }

                                            $stmt->free_result();
                                            $stmt->close();

                                            switch ($authme_db['hash']) {
                                                case 'sha256':
                                                    $exploded = explode('$', $password);
                                                    $salt = $exploded[2];

                                                    $password = $salt . '$' . $exploded[3];

                                                    break;

                                                case 'pbkdf2':
                                                    [$iterations, $salt, $pass] = explode('$', $password);
                                                    $password = $iterations . '$' . $salt . '$' . $pass;

                                                    break;
                                            }

                                            // Update password
                                            if (!is_null($password)) {
                                                if ($method_field === 'email') {
                                                    $user_id = $user->emailToId($username);
                                                } else {
                                                    $user_id = $user->nameToId($username);
                                                }

                                                DB::getInstance()->update('users', $user_id, [
                                                    'password' => $password,
                                                    'pass_method' => $authme_db['hash']
                                                ]);
                                            }
                                        }
                                    }
                                } catch (Exception $e) {
                                    // Error, continue as we can use the already stored password
                                }
                            }

                            try {
                                $login = $user->login($username, Input::get('password'), $remember, $method_field);
                            } catch (GuzzleException|Exception $ignored) {
                            }

                            // Successful login?
                            if ($login) {
                                // Yes
                                Log::getInstance()->log(Log::Action('user/login'));

                                // Redirect to a certain page?
                                if (isset($_SESSION['last_page']) && substr($_SESSION['last_page'], -1) !== '=') {
                                    Redirect::back();
                                } else {
                                    Session::flash('home', $language->get('user', 'successful_login'));
                                    Redirect::to(URL::build('/'));
                                }
                            }

                            // No, output error
                            $return_error = [$language->get('user', 'incorrect_details')];
                        }
                    } else {
                        $return_error = [$language->get('user', 'incorrect_details')];
                    }
                } else {
                    // Validation failed
                    $return_error = $validation->errors();
                }
            } else {
                // reCAPTCHA failed
                $return_error = [$language->get('user', 'invalid_recaptcha')];
            }
        } else {
            // Invalid token
            $return_error = [$language->get('general', 'invalid_token')];
        }
    } catch (GuzzleException|Exception $ignored) {
    }
}

// OAuth session meta
Session::put('oauth_method', 'login');

// Sign in template
// Generate content
if ($login_method === 'email') {
    $smarty->assign('EMAIL', $language->get('user', 'email'));
} else if ($login_method === 'email_or_username') {
    $smarty->assign('USERNAME', $language->get('user', 'email_or_username'));
} else if (MINECRAFT) {
    $smarty->assign('USERNAME', $language->get('user', 'minecraft_username'));
} else {
    $smarty->assign('USERNAME', $language->get('user', 'username'));
}

$smarty->assign([
    'USERNAME_INPUT' => ($login_method === 'email' ? Output::getClean(Input::get('email')) : Output::getClean(Input::get('username'))),
    'PASSWORD' => $language->get('user', 'password'),
    'REMEMBER_ME' => $language->get('user', 'remember_me'),
    'FORGOT_PASSWORD_URL' => URL::build('/forgot_password'),
    'FORGOT_PASSWORD' => $language->get('user', 'forgot_password'),
    'FORM_TOKEN' => Token::get(),
    'SIGN_IN' => $language->get('general', 'sign_in'),
    'REGISTER_URL' => URL::build('/register'),
    'REGISTER' => $language->get('general', 'register'),
    'ERROR_TITLE' => $language->get('general', 'error'),
    'ERROR' => ($return_error ?? []),
    'NOT_REGISTERED_YET' => $language->get('general', 'not_registered_yet'),
    'OAUTH_AVAILABLE' => NamelessOAuth::getInstance()->isAvailable(),
    'OAUTH_PROVIDERS' => NamelessOAuth::getInstance()->getProvidersAvailable(),
    'OR' => $language->get('general', 'or'),
]);

if (Session::exists('oauth_error')) {
    $smarty->assign('ERROR', [Session::flash('oauth_error')]);
} else if (isset($return_error)) {
    $smarty->assign('ERROR', $return_error);
}

if (Session::exists('login_success')) {
    $smarty->assign('SUCCESS', Session::flash('login_success'));
}

if ($captcha) {
    $smarty->assign('CAPTCHA', CaptchaBase::getActiveProvider()->getHtml());
    $template->addJSFiles([CaptchaBase::getActiveProvider()->getJavascriptSource() => []]);

    $submitScript = CaptchaBase::getActiveProvider()->getJavascriptSubmit('form-login');
    if ($submitScript) {
        $template->addJSScript('
            $("#form-login").submit(function(e) {
                e.preventDefault();
                ' . $submitScript . '
            });
        ');
    }
}

// Load modules + template
Module::loadPage($user, $pages, $cache, $smarty, [$navigation, $cc_nav, $staffcp_nav], $widgets, $template);

$template->onPageLoad();

require(ROOT_PATH . '/core/templates/navbar.php');
require(ROOT_PATH . '/core/templates/footer.php');

// Display template
try {
    $template->displayTemplate('login.tpl', $smarty);
} catch (SmartyException $ignored) {
}
