<?php

//=== Arguments
if (isset($argv) and count($argv) > 0)
{
	array_shift($argv); // the file

	while(count($argv))
	{
		$param = array_shift($argv);
		$param = explode('=', $param, 2);

		if (count($param) === 1 and count($argv) === 0)
		{
			$key = 'outputdir';
			$val = array_shift($param);
		}
		elseif(count($param) === 1)
		{
			continue;
		}
		else
		{
			list($key, $val) = $param;
		}

		$config[$key] = $val;
	}
}


//=== Check Configuration
$promted = 0;

foreach ($config as $var => $val)
{
	$val = GitLabAutoSync_get_config_var($var);

	if (empty($val))
	{
		GitLabAutoSync_print('Parámetro faltante "' . $var . '", favor ingreselo o vacío para cancelar el proceso.');
		$val = GitLabAutoSync_Command_promt();

		GitLabAutoSync_print('');
		$promted ++;
	}

	if (empty($val))
		exit;

	$config[$var] = $val;
}

if ($promted > 0)
{
	GitLabAutoSync_print('Se han actualizado algunos datos, ¿Desea guardar la configuración? (yes or no)');
	$val = GitLabAutoSync_Command_promt();

	if (preg_match('/^(y|s|t|1)/i', $val))
		GitLabAutoSync_save_config();
}

@chdir(GitLabAutoSync_HOMEPATH);
return require 'sync.php';