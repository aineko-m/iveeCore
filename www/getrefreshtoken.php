<?php
/**
 * This web script allows users to retrieve refresh tokens for their application from CREST. It has no dependency to
 * other files so it can be used independently from the rest of iveeCrest. It is implemented procedurally because of 
 * retro.
 *
 * Inspired by https://github.com/fuzzysteve/eve-sso-auth
 *
 * PHP version 5
 *
 * @category IveeCrest
 * @package  IveeCrestWeb
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCrest/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCrest/blob/master/www/getrefreshtoken.php
 */

DEFINE('APPLICATION_MANAGEMENT_URL', 'https://developers.eveonline.com/applications');
DEFINE('CREST_BASE_URL', 'https://login.eveonline.com');
DEFINE('CREST_AUTH_URL', '/oauth/authorize');
DEFINE('CREST_TOKEN_URL', '/oauth/token');
DEFINE('USER_AGENT', 'iveeCrest/0.1 (GetRefreshToken)');

session_start();

/**
 * The steps of getting a refresh token are as follows:
 *  - 0: Showing the form for client ID, secret and callback URL
 *  - 1: Receiving the form data via POST, redirecting the user browser to CREST auth
 *  - 2: Receiving a code via redirected user browser from CREST, use it to retrieve refresh token
 *  - 3: Showing refresh token
 */
if (isset($_SESSION['refresh_token'])){
    //we already have a refresh token
    $_SESSION['step'] = 3;
    renderHtml();
} elseif (isset($_GET['code']) AND isset($_SESSION['auth_state']) AND isset($_GET['state']) 
        AND $_SESSION['auth_state'] == $_GET['state'])
    {
    //we received a request with a code parameter
    retrieveToken();
    renderHtml();
} elseif (count($_POST) > 0) {
    //we received a post request
    handlePost();
} else {
    //default case
    $_SESSION['step'] = 0;
    renderHtml();
}

session_write_close();

/**
 * This function represents step 1, processing the form POST data and redirecting the user client browser to CREST auth.
 */
function handlePost()
{
    unset($_SESSION['clientid']);
    unset($_SESSION['clientsecret']);
    unset($_SESSION['callbackurl']);

    if (isset($_POST['clientid']) AND strlen($_POST['clientid']) == 32)
        $_SESSION['clientid'] = $_POST['clientid'];
    if (isset($_POST['clientsecret']) AND strlen($_POST['clientsecret']) == 40)
        $_SESSION['clientsecret'] = $_POST['clientsecret'];
    if (isset($_POST['callbackurl']) AND strlen($_POST['callbackurl']) > 0)
        $_SESSION['callbackurl'] = $_POST['callbackurl'];

    if (isset($_SESSION['clientid']) AND isset($_SESSION['clientsecret']) AND isset($_SESSION['callbackurl'])) {
        $_SESSION['auth_state'] = MD5(microtime() . uniqid());

        header(
            'Location:' . CREST_BASE_URL . CREST_AUTH_URL . '?response_type=code&redirect_uri=' . $_SESSION['callbackurl']
            . '&client_id=' . $_SESSION['clientid'] . '&scope=publicData&state=' . $_SESSION['auth_state']
        );
    } else {
        $_SESSION['step'] = 0;
        renderHtml();
    }
}

/**
 * This function represents step 2, receiving the code from CREST and use it to retrieve the refresh token.
 */
function retrieveToken()
{
    $header = 'Authorization: Basic ' . base64_encode($_SESSION['clientid'] . ':' . $_SESSION['clientsecret']);
    $fields_string = '';
    $fields = array(
        'grant_type' => 'authorization_code',
        'code' => $_GET['code']
    );
    foreach ($fields as $key => $value) {
        $fields_string .= $key . '=' . $value . '&';
    }
    rtrim($fields_string, '&');
    $ch = curl_init();
    
    curl_setopt_array(
        $ch,
        array(
            CURLOPT_URL             => CREST_BASE_URL . CREST_TOKEN_URL,
            CURLOPT_POST            => true,
            CURLOPT_POSTFIELDS      => $fields_string,
            CURLOPT_HTTPHEADER      => array($header),
            CURLOPT_RETURNTRANSFER  => true,
            CURLOPT_USERAGENT       => USER_AGENT,
            CURLOPT_SSL_VERIFYPEER  => true,
            CURLOPT_SSL_CIPHER_LIST => 'TLSv1', //prevent protocol negotiation fail
        )
    );

    $resBody = curl_exec($ch);
    $info = curl_getinfo($ch);
    $err  = curl_errno($ch);
    $errmsg = curl_error($ch);

    if ($err != 0)
        throw new Exception($errmsg, $err);
    if (!in_array($info['http_code'], array(200, 302)))
        throw new Exception(
            'HTTP response not OK: ' . (int)$info['http_code'] . '. Response body: ' . $resBody,
            $info['http_code']
        );

    curl_close($ch);
    $response = json_decode($resBody);
    
    $_SESSION['refresh_token'] = $response->refresh_token;
    $_SESSION['step'] = 3;
}

/**
 * This function outputs the basic HTML skeleton and calls content().
 */
function renderHtml()
{
    ?><!DOCTYPE html>
<html>
    <head>
        <title>iveeCrest: Getting a refresh token</title>
        <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.1/css/bootstrap.min.css">
    </head>
    <body>
        <div class="container">
            <div class="row">
                <div class="col-md-9">
                    <h3 class="page-header">iveeCrest: Getting a refresh token</h3>
                    <?php content(); ?>
                </div>
            </div>
        </div>
    </body>
</html><?php
}

/**
 * This function outputs step dependant HTML content.
 */
function content()
{
    switch ($_SESSION['step']) {
        case 3:
            echo '<p>A refresh token has been succesfully received from CREST.</p>'
            . '<div class="alert alert-success" role="alert">Refresh token: <b>' . $_SESSION['refresh_token'] 
            . '</b></div>'
            . '<p>This token can now be used in conjunction with the client ID and secret to enable applications access '
            . 'to authenticated CREST.</p>'
            . '<p>A refresh token has no expiry, but it can be revoked under '
            . '<a href="https://community.eveonline.com/support/third-party-applications/">'
            . 'https://community.eveonline.com/support/third-party-applications/</a></p>'
            . '<p>This token is character specific, altough with the publicData scope no private character '
            . 'data can be obtained.</p>';
            break;
        case 0:
        default:
            $clientId     = isset($_SESSION['clientid']) ? $_SESSION['clientid'] : '';
            $clientSecret = isset($_SESSION['clientsecret']) ? $_SESSION['clientsecret'] : '';
            $callbackUrl  = isset($_SESSION['callbackurl']) ? $_SESSION['callbackurl'] : get_current_url();

            echo '<p>This web script allows users/developers to get refresh tokens for their applications.</p>
            <p>First, go to <a href="' . APPLICATION_MANAGEMENT_URL . '">' . APPLICATION_MANAGEMENT_URL . '</a>, 
            register if necessary, then create a new application, selecting "CREST Access" and the publicData 
            scope. Set the appropriate callback URL (the address of this script). Then fill the form below with the 
            given client ID and secret as well as the identical callback URL.</p>
            <p>When you click the login button you\'ll be redirected to the Eve CREST authentication page where 
            you can authorized the application. Once you\'ve done this you\'ll be redirected to this script.</p><br />
            <div class="panel panel-default"><div class="panel-body">
            <form class="form-horizontal" action="getrefreshtoken.php" method="POST">
            <div class="form-group">
            <label for="clientid" class="col-sm-3 control-label">Application Client ID:</label>
                <div class="col-sm-9">
                <input type="text" class="form-control" id="clientid" name="clientid" maxlength="32" value="' 
                    . $clientId . '" placeholder="Enter client ID">
                </div>
            </div>
            <div class="form-group">
            <label for="clientsecret" class="col-sm-3 control-label">Client Secret Key:</label>
                <div class="col-sm-9">
                <input type="text" class="form-control" id="clientsecret" name="clientsecret" maxlength="40" value="' 
                    . $clientSecret . '" placeholder="Enter client secret">
                </div>
            </div>
            <div class="form-group">
            <label for="callbackurl" class="col-sm-3 control-label">Callback URL:</label>
                <div class="col-sm-9">
                <input type="text" class="form-control" id="callbackurl" name="callbackurl" maxlength="255" value="' 
                    . $callbackUrl . '" placeholder="Enter callback URL">
                </div>
            </div><br />
            <div class="form-group">
                <div class="col-sm-offset-3 col-sm-9">
                  <input type="image" src="https://images.contentful.com/idjq7aai9ylm/4PTzeiAshqiM8osU2giO0Y/5cc4cb60bac52422da2e45db87b6819c/EVE_SSO_Login_Buttons_Large_White.png" alt="Submit">
                </div>
              </div></form></div></div>';
            break;
    }
}

/**
 * Helper function to determine the current script URL.
 */
function get_current_url()
{
    $protocol = 'http';
    $protocol_port = '';
    if (isset($_SERVER['HTTPS']) AND $_SERVER['HTTPS'] == 'on') {
        $protocol .= 's';
        if ($_SERVER['SERVER_PORT'] != 443)
            $protocol_port = ':' . $_SERVER['SERVER_PORT'];
    } elseif ($_SERVER['SERVER_PORT'] != 80)
        $protocol_port = ':' . $_SERVER['SERVER_PORT'];

    return $protocol . '://' . $_SERVER['HTTP_HOST'] . $protocol_port . $_SERVER['PHP_SELF'];
}