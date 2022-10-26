<?php
isset($config) or exit('You need to load bootstrap.php');

$client = new Gitlab\Client();
$client->authenticate($config['httptoken'], Gitlab\Client::AUTH_HTTP_TOKEN);

$zipcontent = $client
	-> repositories()
	-> archive ($config['projectid'], [
		'sha' => $config['branch'],
	], 'zip');

if ( ! file_exists($config['outputdir']))
	@mkdir($config['outputdir'], 0755, true);

$zipfile = $config['outputdir'] . DS . 'GitLabAutoSync.' . uniqid(time() . '_') . '.zip';
file_put_contents($zipfile, $zipcontent);

GitLabAutoSync_clean_directory ($config['outputdir']);
GitLabAutoSync_zip_extract_to  ($zipfile, $config['outputdir']);

@unlink($zipfile); // this removes the file