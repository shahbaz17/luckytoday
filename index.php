<?php
session_start();
require_once __DIR__ . '/src/Facebook/autoload.php';

$fb = new Facebook\Facebook([
  'app_id' => '1650032405273722',
  'app_secret' => '094a08edd2d47d4f73b3a1aadfe51701',
  'default_graph_version' => 'v2.5',
]);

$helper = $fb->getCanvasHelper();

$permissions = ['email']; // optionnal
$permissions = ['user_friends']; // optionnal

try {
	if (isset($_SESSION['facebook_access_token'])) {
	$accessToken = $_SESSION['facebook_access_token'];
	} else {
  		$accessToken = $helper->getAccessToken();
	}
} catch(Facebook\Exceptions\FacebookResponseException $e) {
 	// When Graph returns an error
 	echo 'Graph returned an error: ' . $e->getMessage();
  	exit;
} catch(Facebook\Exceptions\FacebookSDKException $e) {
 	// When validation fails or other local issues
	echo 'Facebook SDK returned an error: ' . $e->getMessage();
  	exit;
 }

if (isset($accessToken)) {

	if (isset($_SESSION['facebook_access_token'])) {
		$fb->setDefaultAccessToken($_SESSION['facebook_access_token']);
	} else {
		$_SESSION['facebook_access_token'] = (string) $accessToken;

	  	// OAuth 2.0 client handler
		$oAuth2Client = $fb->getOAuth2Client();

		// Exchanges a short-lived access token for a long-lived one
		$longLivedAccessToken = $oAuth2Client->getLongLivedAccessToken($_SESSION['facebook_access_token']);

		$_SESSION['facebook_access_token'] = (string) $longLivedAccessToken;

		$fb->setDefaultAccessToken($_SESSION['facebook_access_token']);
	}

	// validating the access token
	try {
		$request = $fb->get('/me');
	} catch(Facebook\Exceptions\FacebookResponseException $e) {
		// When Graph returns an error
		if ($e->getCode() == 190) {
			unset($_SESSION['facebook_access_token']);
			$helper = $fb->getRedirectLoginHelper();
			$loginUrl = $helper->getLoginUrl('https://apps.facebook.com/simubasicinfo', $permissions);
			echo "<script>window.top.location.href='".$loginUrl."'</script>";
			exit;
		}
	} catch(Facebook\Exceptions\FacebookSDKException $e) {
		// When validation fails or other local issues
		echo 'Facebook SDK returned an error: ' . $e->getMessage();
		exit;
	}

	// getting basic info about user
	try {
		$profile_request = $fb->get('/me?fields=name,email');
		$profile = $profile_request->getGraphNode()->asArray();
		$requestPicture = $fb->get('/me/picture?redirect=false&height=200'); //getting user picture
		$picture = $requestPicture->getGraphUser();
		//$requestFriends = $fb->get('/me/taggable_friends?fields=name&limit=100');
		//$friends = $requestFriends->getGraphEdge();
	} catch(Facebook\Exceptions\FacebookResponseException $e) {
		// When Graph returns an error
		echo 'Graph returned an error: ' . $e->getMessage();
		unset($_SESSION['facebook_access_token']);
		echo "<script>window.top.location.href='https://apps.facebook.com/simubasicinfo'</script>";
		exit;
	} catch(Facebook\Exceptions\FacebookSDKException $e) {
		// When validation fails or other local issues
		echo 'Facebook SDK returned an error: ' . $e->getMessage();
		exit;
	}

	// priting basic info about user on the screen
	//print_r($profile);
	echo "<img src='".$picture['url']."'/><br/>";
	echo $profile['name']."<br/>";
	//echo $profile['email']."<br/>";
	//echo $profile['id']."<br/>";


	/*if ($fb->next($friends)) {
		$allFriends = array();
		$friendsArray = $friends->asArray();
		$allFriends = array_merge($friendsArray, $allFriends);
		while ($friends = $fb->next($friends)) {
			$friendsArray = $friends->asArray();
			$allFriends = array_merge($friendsArray, $allFriends);
		}
		foreach ($allFriends as $key) {
			echo $key['name'] . "<br>";
		}
		echo count($allFriends);
	} else {
		$allFriends = $friends->asArray();
		$totalFriends = count($allFriends);
		foreach ($allFriends as $key) {
			echo $key['name'] . "<br>";
		}
	}*/

  	// Now you can redirect to another page and use the access token from $_SESSION['facebook_access_token']
} else {
	$helper = $fb->getRedirectLoginHelper();
	$loginUrl = $helper->getLoginUrl('https://apps.facebook.com/simubasicinfo', $permissions);
	echo "<script>window.top.location.href='".$loginUrl."'</script>";
}
