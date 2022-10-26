<?php
session_start();

if ( ! isset($_SESSION['webtoken']))
	$_SESSION['webtoken'] = uniqid(time() . '_', true);

$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : (isset($_SERVER['HTTP_X_GITLAB_TOKEN']) ? 'AutoSync' : 'Sync');


//=== Save the $_POST

if ($action === 'SaveConfig')
{
	$token = isset($_REQUEST['token']) ? $_REQUEST['token'] : null;

	if ($token <> $_SESSION['webtoken'])
	{
		?>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.2/dist/css/bootstrap.min.css" rel="stylesheet">

<div class="container mt-4 mb-n3">
	<div class="row justify-content-center">
		<div class="col-12 col-md-6 col-lg-4">
			<div class="alert alert-danger">
				Token inválido
			</div>
		</div>
	</div>
</div>

		<?php
		goto check_configuration;
	}

	$newconfig = isset($_REQUEST['config']) ? $_REQUEST['config'] : [];
	$newconfig = (array) $newconfig;

	foreach ($newconfig as $key => $val)
	{
		if ($key === 'outputdir' and $val === '__DIR__')
			$val = dirname($_SERVER['SCRIPT_FILENAME']);

		$config[$key] = $val;
	}

	GitLabAutoSync_save_config();
}


//=== Check Configuration
check_configuration:

$faltan = [];

foreach ($config as $var => $val)
{
	$val = GitLabAutoSync_get_config_var($var);

	if (empty($val))
	{
		$faltan[] = $var;
		continue;
	}

	$config[$var] = $val;
}

if (count($faltan) > 0)
{
	$_SESSION['webtoken'] = uniqid(time() . '_', true); ## regenerando
	?>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.2/dist/css/bootstrap.min.css" rel="stylesheet">

<div class="container my-4">
	<div class="row justify-content-center">
		<div class="col-12 col-md-6 col-lg-4">
			<div class="card shadow-sm">
				<div class="card-body">
					<h4>Configuración</h4>

					<form method="post">
						<input type="hidden" name="action" value="SaveConfig" />
						<input type="hidden" name="token" value="<?= htmlspecialchars($_SESSION['webtoken']); ?>" />

						<?php foreach ($faltan as $key) : ?>
						<div class="form-group mb-2">
							<label>Ingrese parámetro <code>"<?= $key; ?>"</code></label>
							<input type="text" name="config[<?= $key; ?>]" placeholder="<?= $key; ?>" class="form-control" />

							<?php if ($key === 'outputdir') : ?>
							<small class="text-muted user-select-none">Ingrese <code class="user-select-all">__DIR__</code> si desea que se asigne el directorio del archivo ejecutado.</small>
							<?php endif; ?>

							<?php if ($key === 'branch') : ?>
							<small class="text-muted user-select-none">La rama por defecto es <code class="user-select-all">main</code>.</small>
							<?php endif; ?>
						</div>
						<?php endforeach; ?>

						<button type="submit" class="btn btn-primary">Guardar</button>
					</form>
				</div>
			</div>
		</div>
	</div>
</div>

	<?php
	exit;
}


//=== If is a GitLab Event
if ($action === 'AutoSync')
	goto sync_process;


//=== Check the page is in hooks of gitlab

$actual_uri_https = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http');
$actual_uri_host  = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '';
$actual_uri_path  = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
$actual_uri = $actual_uri_https . '://' . $actual_uri_host . $actual_uri_path;

$actual_uri_key = 'uri://' . $actual_uri_host . $actual_uri_path;
if ( ! isset($config[$actual_uri_key]))
{
	$_SESSION['webtoken'] = uniqid(time() . '_', true); ## regenerando
	?>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.2/dist/css/bootstrap.min.css" rel="stylesheet">

<div class="container my-4">
	<div class="row justify-content-center">
		<div class="col-12 col-md-6 col-lg-4">
			<div class="card shadow-sm">
				<div class="card-body">
					<h4>Configuración</h4>

					<form method="post">
						<input type="hidden" name="action" value="SaveConfig" />
						<input type="hidden" name="token" value="<?= htmlspecialchars($_SESSION['webtoken']); ?>" />

						<div class="form-group mb-2">
							<label>Auto-Añadir Hook en GitLab <code>(<?= $actual_uri; ?>)</code></label>
							<select name="config[<?= htmlspecialchars($actual_uri_key); ?>]" class="form-select">
								<option value="" disabled selected>Sel. Opción</option>
								<option value="yes">SI</option>
								<option value="no">NO</option>
							</select>
						</div>

						<button type="submit" class="btn btn-primary">Guardar</button>
					</form>
				</div>
			</div>
		</div>
	</div>
</div>

	<?php
	exit;
}

$check_hook_is_added = $config[$actual_uri_key];
if ( ! preg_match('/^(y|s|t|1)/i', $check_hook_is_added))
	goto sync_process;


$client = new Gitlab\Client();
$client->authenticate($config['httptoken'], Gitlab\Client::AUTH_HTTP_TOKEN);

$pager = new Gitlab\ResultPager($client);
$hooks = $pager->fetchAll($client->projects(), 'hooks', [$config['projectid']]);
$hooks_uris = array_map(function($hook){
	return $hook['url'];
}, (array) $hooks);

if (in_array($actual_uri, $hooks_uris))
	goto sync_process;

$client->projects()
-> addHook($config['projectid'], $actual_uri, [
	'confidential_issues_events' => 0,
	'confidential_note_events'   => 0,
	'deployment_events'          => 0,
	'issues_events'              => 0,
	'job_events'                 => 0,
	'note_events'                => 0,
	'pipeline_events'            => 0,
	'wiki_page_events'           => 0,
	'repository_update_events'   => 0,

	'merge_requests_events' => 1,
	'push_events'           => 1,
	'releases_events'       => 1,
	'tag_push_events'       => 1,

	'token' => 'GitLabAutoSync-' . time(),

	'enable_ssl_verification' => ($actual_uri_https === 'https' ? 1 : 0)
]);

{
	?>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.2/dist/css/bootstrap.min.css" rel="stylesheet">

<div class="container mt-4 mb-n3">
	<div class="row justify-content-center">
		<div class="col-12 col-md-6 col-lg-4">
			<div class="alert alert-success">
				Hook añadido a GitLab
			</div>
		</div>
	</div>
</div>
	<?php
}
exit;




//=== Sync Files
sync_process:

@chdir(GitLabAutoSync_HOMEPATH);
require 'sync.php';

if ($action === 'AutoSync')
	die(date('d/m/Y h:i:s A'));

{
	?>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.2/dist/css/bootstrap.min.css" rel="stylesheet">

<div class="container mt-4 mb-n3">
	<div class="row justify-content-center">
		<div class="col-12 col-md-6 col-lg-4">
			<div class="alert alert-success">
				Proyecto sincronizado<br>
				<code><?= date('d/m/Y h:i:s A'); ?></code>
			</div>
		</div>
	</div>
</div>
	<?php
}