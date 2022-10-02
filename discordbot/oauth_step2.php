<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title></title>
</head>


<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

error_reporting(E_ALL);

session_start();


include 'secrets.php';
include 'functions.php';


$apiURLLogin = "https://cas-p.wigorservices.net/cas/login?service=https://ws-edt-cd.wigorservices.net/WebPsDyn.aspx";



if ($_SERVER['REQUEST_METHOD'] === 'POST') {

	// Obtain Execution token
	$getToken = doApiRequest($apiURLLogin, returnjson: FALSE);

	$dom = new DomDocument();
	$domm = $dom->loadHTML($getToken, LIBXML_NOERROR);
	$xpath = new DOMXpath($dom);

	$xpathquery = "//input[@name='execution']";
	$elements = $xpath->query($xpathquery);

	$token = $elements[0]->getAttribute('value');


	// Do login request
	$headers[] = "Accept: text/html";
	$headers[] = "Content-Type: application/x-www-form-urlencoded";
	$headers[] = "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/105.0.0.0 Safari/537.36";


	$data = array(
		"username" => $_POST['username'],
		"password" => $_POST['password'],
		"execution" => $token,
		"_eventId" => "submit",
		"geolocation" => "" 
	);

	$checkLogin = doApiRequest(
		$apiURLLogin,
		post: $data,
		headers: $headers,
		returnstatuscodeandheaders: TRUE
	);

	if ($checkLogin->httpcode == 200) {
		echo 'Successfully logged in as ' . $_POST['username'] . ':' . $_POST['password'];

		$_SESSION['username'] = $_POST['username'];
		$_SESSION['password'] = $_POST['password'];

		$_SESSION['wigorservices_cookies'] = "_DotNetCasClientAuth=".$checkLogin->cookies['_DotNetCasClientAuth']."; ASP_NET_SessionId=".$checkLogin->cookies['ASP_NET_SessionId'];

		$_SESSION['username'] = $_POST['username'];
		$_SESSION['firstname'] = ucfirst( explode('.', $_POST['username'])[0] );
		$_SESSION['lastname'] = ucfirst( explode('.', $_POST['username'])[1] );


		header("Location: " . SUCCESS_URL);
		die();

	} else {
		echo 'you suck ' . $checkLogin->httpcode;
		echo '<br><br><br><br>';
		echo doApiRequest(
			$apiURLLogin,
			post: array(
				"username" => $_POST['username'],
				"password" => $_POST['password'],
				"execution" => $token,
				"_eventId" => "submit",
				"geolocation" => "" 
			),
			headers: array(
				"Accept" => "text/html",
				"content-type" => "application/x-www-form-urlencoded",
				"user-agent" => "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/105.0.0.0 Safari/537.36"
			),
			returnjson: FALSE
		);
		return;
	}



} else {

	if (!session('access_token')) {
		echo 'not logged in';

		header('Location: ' . REDIRECT_URL . '?action=login');
	} else {
		//echo 'logged in';

		$user = doApiRequest($apiURLBase);

		if ( (property_exists($user, 'code')) AND ($user->code or 1 === 0) ) {
			echo 'session expired';
	
			header('Location: ' . REDIRECT_URL . '?action=login');
		}

	}
}


?>


<style type="text/css">
	body {
		width: 100vw; height: 100vh; margin: 0;
		display: flex;
		justify-content: center;
		align-items: center;
	}
	.background {
		width: 105vw; height: 105vh; margin: 0;
		position: fixed;
		z-index: -1;
		background-image: url('https://t4.ftcdn.net/jpg/01/19/11/55/360_F_119115529_mEnw3lGpLdlDkfLgRcVSbFRuVl6sMDty.jpg');
		background-repeat: no-repeat;
		background-size: cover;

		filter: brightness(0.3) blur(3px);
		-webkit-filter: brightness(0.3) blur(3px);
	}

	@font-face {
		font-family: "Uni Sans";
		src: url("UniSansHeavy.otf");
	}

	.form {
		min-width: 55%; height: 80%; border-radius: 10px;

		background-color: #8290a559;
		
		text-align: center;
		display: flex;
		align-items: center;
		flex-direction: column;	}

	.spacing {padding: 1.5rem; border-top: 1px solid black; width: 80%;}

	.form form, .form .discord {
		display: flex;
		flex-direction: column;
		align-items: center;
	}

	.discord {
		padding: 2rem;
	}
	.discord img {
		width: 70%;
		min-width: 70%;
		height: auto;

		border-radius: 50%;
	}

	span#username {font-family: "Uni Sans", sans-serif; font-size: 2rem; padding-top: 1rem;}
	span#id {font-size: 1rem;}


	input.credentials {
		width: 75%
		height: 2rem;
		margin: .5rem;

		font-size: 1.3rem;
	}
	input[type=submit] {
		padding: .5rem 2rem;
		font-size: 1.2rem;
		margin: 2rem;

		background-color: #005aff;
		color: #FBFBFB;
	}

	h3.liaison {
		margin-block-start: 0; margin-block-end: 2em; font-family: sans-serif;
		padding: 0 1rem;
	}

</style>

<body>
<div class="background"></div>

<div class="form">

	<div class="discord">
		<img src=<?php echo 'https://cdn.discordapp.com/avatars/' . $user->id . '/' . $user->avatar . '.png'; ?>>
		<span id="username"><?php echo $user->username . '#' . $user->discriminator; ?></span>
		<span id="id"><?php echo $user->id;?></span>
	</div>

	<div class="spacing"></div>

	<form method="POST">
		<h3 class="liaison">Lier vos comptes Discord et 360 Learning</h3>
		<input class="credentials" type="text" name="username" placeholder="identifiant">
		<input class="credentials" type="password" name="password" placeholder="mot de passe">

		<input type="submit" name="Se connecter" value="Valider">
	</form>
</div>
	
</body>
</html>