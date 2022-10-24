<?php

//== bye buffer
while(ob_get_level())
	ob_end_clean();


//=== VARIABLES

defined('DS') or define('DS', DIRECTORY_SEPARATOR);

defined('PHAR_SOURCE') or define('PHAR_SOURCE', __DIR__ . '/src');
defined('PHAR_OUTPUT') or define('PHAR_OUTPUT', __DIR__ . '/dist/GitLabAutoSync.phar');


//=== CLEANING

if (file_exists(PHAR_OUTPUT))
	unlink(PHAR_OUTPUT);

if (file_exists(PHAR_OUTPUT . '.gz'))
	unlink(PHAR_OUTPUT . '.gz');


//=== CREATING PHAR

$phar = new Phar(PHAR_OUTPUT);
$phar->buildFromDirectory(PHAR_SOURCE);

$phargz = $phar->compress(Phar::GZ);
$phargz->setStub('<?php return include (\'phar://\' . __FILE__ . \'/bootstrap.php\'); __HALT_COMPILER();');

if (file_exists(PHAR_OUTPUT))
	unlink(PHAR_OUTPUT);


//=== CRATING INDEX.PHP
$index_content = '<?php' . PHP_EOL;
$index_content.= 'chdir(__DIR__);' . PHP_EOL;
$index_content.= 'defined(\'GitLabAutoSync_CONFIGFILE\') or define(\'GitLabAutoSync_CONFIGFILE\', __DIR__ . \'/config.php\');' . PHP_EOL;
$index_content.= 'return require_once \'phar://' . basename(PHAR_OUTPUT) . '.gz\';' . PHP_EOL;

file_put_contents(dirname(PHAR_OUTPUT) . DS . basename(PHAR_OUTPUT, '.phar') . '.php', $index_content);


//=== HELPERS

function PharAddDirFilesRecursive (Phar $phar, string $directory)
{
	$files = scandir($directory);

	foreach ($files as $file)
	{
		if (in_array($file, ['.', '..']))
			continue;

		$file = $directory . DS . $file;

		if (is_dir($file))
		{
			PharAddDirFilesRecursive($phar, $file);
			continue;
		}

		$filename = str_replace(PHAR_SOURCE, '', $file);
		$filename = str_replace(DS, '/', $filename);
		$filename = ltrim($filename, '/');

		$phar->addFile($file, $filename);
		echo $file . ' -> ' . $filename . PHP_EOL;
	}
}