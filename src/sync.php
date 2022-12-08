<?php
isset($config) or exit('You need to load bootstrap.php');

GitLabAutoSync_print ('[Sincronización] Iniciando...');

$client = new Gitlab\Client();
$client->authenticate($config['httptoken'], Gitlab\Client::AUTH_HTTP_TOKEN);

GitLabAutoSync_print ('[Sincronización] Obteniendo el ZIP');

$zipcontent = $client
	-> repositories()
	-> archive ($config['projectid'], [
		'sha' => $config['branch'],
	], 'zip');

if ( ! file_exists($config['outputdir']))
	@mkdir($config['outputdir'], 0755, true);

GitLabAutoSync_print ('[Sincronización] Guardando ZIP');

$zipfile = $config['outputdir'] . DS . 'GitLabAutoSync.' . uniqid(time() . '_') . '.zip';
file_put_contents($zipfile, $zipcontent);

GitLabAutoSync_print ('[Sincronización] Descomprimiendo ZIP');

if ($config['clean_dir'] === 'yes')
	GitLabAutoSync_clean_directory ($config['outputdir']);

GitLabAutoSync_zip_extract_to  ($zipfile, $config['outputdir']);

GitLabAutoSync_print ('[Sincronización] Eliminando ZIP');

@unlink($zipfile); // this removes the file

GitLabAutoSync_print ('[Sincronización] Finalizado');