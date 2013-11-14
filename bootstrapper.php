<?php
/**
 * The bootstrapper makes sure we have all services available to the console when we run it
 * It depends on the autoloader being registered before-hand using the statement below
 * In this case we use the autoload file generated by composer
 */

use Silex\Application;
use Igorw\Silex\ConfigServiceProvider;
use Knp\Provider\ConsoleServiceProvider;
use Neutron\Silex\Provider\FilesystemServiceProvider;
use Dflydev\Silex\Provider\DoctrineOrm\DoctrineOrmServiceProvider;
use Silex\Provider\DoctrineServiceProvider;
use Symfony\Component\Console\Helper\HelperSet;
use \Doctrine\DBAL\Tools\Console\Helper\ConnectionHelper;
use \Doctrine\ORM\Tools\Console\Helper\EntityManagerHelper;

$configPath = PROGEN_WORKER_DIR . "/config.php";
$app = new Application();
$app->register(new FilesystemServiceProvider($app));
$app->register(new ConfigServiceProvider($configPath));
$app->register(new ConsoleServiceProvider(), array(
    'console.name'              => 'Progen Worker (Development)',
    'console.version'           => '1.0.0',
    'console.project_directory' => __DIR__.'/..'
));

$app->register(new DoctrineServiceProvider(), array(
    "db.options" => array(
        'dbname' => $app['progen']['database']['name'],
        'username' => $app['progen']['database']['username'],
        'password' => $app['progen']['database']['password'],
        'host' => $app['progen']['database']['host'],
        'port' => $app['progen']['database']['port'],
    )
));

$app->register(new DoctrineOrmServiceProvider(), array(
    "orm.proxies_dir" => __DIR__ . "/cache/proxies",
    "orm.em.options" => array(
        "mappings" => array(
            // Using actual filesystem paths
            array(
                "use_simple_annotation_reader" => false,
                "type" => "annotation",
                "namespace" => "Cleentfaar\\ProGen\\Worker\\Base\\Entity",
                "path" => __DIR__ . "/src/Cleentfaar/ProGen/Worker/Base/Entity",
            ),
        ),
    ),
));

$console = $app['console'];

/*
 * Invocation of the Doctrine CLI
 * This is handy for quick entity updates after adjustments to the main (GUI) entities, among other cases
 */
$helperSet = new HelperSet(array(
    'db' => new ConnectionHelper($app['orm.em']->getConnection()),
    'em' => new EntityManagerHelper($app['orm.em'])
));
/**
 * Note: The commands added below are slightly less then those available normally
 * This is because within a worker we don't want to allow the creation or modification of the database scheme,
 * which could be hosted remotely. In short: we shouldn't pass those commands to the CLI
 *
 * @see \Doctrine\ORM\Tools\Console\ConsoleRunner::addCommands
 */
$console->setHelperSet($helperSet);
$doctrineCommands = array(
    new \Doctrine\DBAL\Tools\Console\Command\ImportCommand(),
    new \Doctrine\ORM\Tools\Console\Command\ClearCache\MetadataCommand(),
    new \Doctrine\ORM\Tools\Console\Command\EnsureProductionSettingsCommand(),
    new \Doctrine\ORM\Tools\Console\Command\ConvertDoctrine1SchemaCommand(),
    new \Doctrine\ORM\Tools\Console\Command\GenerateRepositoriesCommand(),
    new \Doctrine\ORM\Tools\Console\Command\GenerateEntitiesCommand(),
    new \Doctrine\ORM\Tools\Console\Command\GenerateProxiesCommand(),
    new \Doctrine\ORM\Tools\Console\Command\ConvertMappingCommand(),
    new \Doctrine\ORM\Tools\Console\Command\RunDqlCommand(),
    new \Doctrine\ORM\Tools\Console\Command\ValidateSchemaCommand(),
    new \Doctrine\ORM\Tools\Console\Command\InfoCommand()
);
$console->addCommands($doctrineCommands);
$console->add(new \Cleentfaar\ProGen\Worker\Base\Command\CronCommand());

return $console;