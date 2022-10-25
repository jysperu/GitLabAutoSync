<?php

@error_reporting(-1);

while(@ob_get_level())
	@ob_end_clean();

defined('DS') or define('DS', DIRECTORY_SEPARATOR);
defined('IS_COMMAND') or define('IS_COMMAND', PHP_SAPI === 'cli');

defined('GitLabAutoSync_HOMEPATH') or define('GitLabAutoSync_HOMEPATH', dirname(__FILE__));
@chdir(GitLabAutoSync_HOMEPATH);

spl_autoload_register(function($class) {
	$class = trim($class, '\\');
	$parts = explode('\\', $class);

	$srcpath = GitLabAutoSync_HOMEPATH . DS . 'lib';

	$classpath = $srcpath . DS . $class . '.php';

	if ( ! file_exists($classpath))
		return;

	require_once $classpath;
});

$config = [
	'httptoken' => '',
	'projectid' => '',
	'outputdir' => '',
];

defined('GitLabAutoSync_CONFIGFILE') or define('GitLabAutoSync_CONFIGFILE', GitLabAutoSync_HOMEPATH . '/config.php');
if ( file_exists(GitLabAutoSync_CONFIGFILE))
	require_once GitLabAutoSync_CONFIGFILE;

if ( ! function_exists('GitLabAutoSync_get_config_var'))
{
	function GitLabAutoSync_get_config_var (string $key)
	{
		global $config;

		if (isset($config[$key]) and ! empty($config[$key]))
			return $config[$key];

		if (defined($key))
			return constant($key);

		if ($val = getenv($key))
			return $val;

		if (isset($_REQUEST[$key]))
			return $_REQUEST[$key];

		$key = 'GitLabAutoSync_' . $key;

		if (defined($key))
			return constant($key);

		if ($val = getenv($key))
			return $val;

		if (isset($_REQUEST[$key]))
			return $_REQUEST[$key];

		return null;
	}
}

if ( ! function_exists('GitLabAutoSync_print'))
{
	function GitLabAutoSync_print (string $message)
	{
		echo $message;

		if (IS_COMMAND)
			echo PHP_EOL;
	}
}

if ( ! function_exists('GitLabAutoSync_save_config'))
{
	function GitLabAutoSync_save_config ()
	{
		global $config;

		$config_php = '<?php' . PHP_EOL;
		$config_php.= '## Guardado el ' . date('d/m/Y h:i:s A') . PHP_EOL;
		$config_php.= PHP_EOL;

		foreach ($config as $var => $val)
		{
			$var = str_replace('\'', '\\\'', $var);
			$val = str_replace('\'', '\\\'', $val);

			$config_php.= '$config[\'' . $var . '\'] = \'' . $val . '\';' . PHP_EOL;
		}

		file_put_contents(GitLabAutoSync_CONFIGFILE, $config_php);
	}
}

if ( ! function_exists('GitLabAutoSync_Command_promt'))
{
	function GitLabAutoSync_Command_promt ()
	{
		$handle = fopen ('php://stdin', 'rb');
		$line   = fgets($handle);

		$line = trim($line);
		return $line;
	}
}

if ( ! function_exists('GitLabAutoSync_clean_directory'))
{
	function GitLabAutoSync_clean_directory ($directory)
	{
		$files = scandir($directory);

		foreach ($files as $file)
		{
			if (in_array($file, ['.', '..']))
				continue;

			if (preg_match('/^GitLabAutoSync/i', $file))
				continue;

			$file = $directory . DS . $file;

			if (is_dir($file))
			{
				GitLabAutoSync_clean_directory($file);
				@rmdir($file);
			}

			if ( ! file_exists($file))
				continue;

			@unlink($file);
		}
	}
}

if ( ! file_exists('GitLabAutoSync_zip_extract_to'))
{
	function GitLabAutoSync_zip_extract_to ($zipfile, $outputdir)
	{
		if ( ! file_exists($zipfile))
			return 'Archivo ZIP no existe';

		if ( ! file_exists($outputdir))
			return 'Directorio destino no existe';

		$outputdir.= DS;

		$zip = new ZipArchive;

		if ($zip->open($zipfile) !== true)
			return 'No se pudo abrir el archivo ZIP';

		$subdir = null;

		for ($i = 0; $i < $zip -> numFiles; $i++)
		{
			$filename = $zip -> getNameIndex($i);

			if (is_null($subdir))
			{
				$subdir = $filename;
				continue;
			}

			if (substr($filename, 0, mb_strlen($subdir)) <> $subdir)
				continue;

			$relative_path = substr($filename, mb_strlen($subdir));
			$relative_path = str_replace(['/', '\\'], DS, $relative_path);

			
            if (mb_strlen($relative_path) === 0)
				continue;

			
			if (substr($filename, -1) == '/')
			{ ## New directory
				if (is_dir($outputdir . $relative_path))
					continue;

				@mkdir($outputdir . $relative_path, 0755, true);
				continue;
			}

			$relative_dir = dirname($relative_path);

			if ($relative_dir !== '.' and  ! is_dir($outputdir . $relative_dir))
				@mkdir($outputdir . $relative_dir, 0755, true);

			## New file
			@file_put_contents($outputdir . $relative_path, $zip->getFromIndex($i));
		}

		$zip -> close();

		return true;
	}
}

if ( ! function_exists('getallheaders'))
{

    /**
     * Get all HTTP header key/values as an associative array for the current request.
     *
     * @return string[string] The HTTP header key/value pairs.
     */
    function getallheaders()
    {
        $headers = array();

        $copy_server = array(
            'CONTENT_TYPE'   => 'Content-Type',
            'CONTENT_LENGTH' => 'Content-Length',
            'CONTENT_MD5'    => 'Content-Md5',
        );

        foreach ($_SERVER as $key => $value) {
            if (substr($key, 0, 5) === 'HTTP_') {
                $key = substr($key, 5);
                if (!isset($copy_server[$key]) || !isset($_SERVER[$key])) {
                    $key = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', $key))));
                    $headers[$key] = $value;
                }
            } elseif (isset($copy_server[$key])) {
                $headers[$copy_server[$key]] = $value;
            }
        }

        if (!isset($headers['Authorization'])) {
            if (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
                $headers['Authorization'] = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
            } elseif (isset($_SERVER['PHP_AUTH_USER'])) {
                $basic_pass = isset($_SERVER['PHP_AUTH_PW']) ? $_SERVER['PHP_AUTH_PW'] : '';
                $headers['Authorization'] = 'Basic ' . base64_encode($_SERVER['PHP_AUTH_USER'] . ':' . $basic_pass);
            } elseif (isset($_SERVER['PHP_AUTH_DIGEST'])) {
                $headers['Authorization'] = $_SERVER['PHP_AUTH_DIGEST'];
            }
        }

        return $headers;
    }

}

return require (IS_COMMAND ? 'command' : 'web') . '.php';