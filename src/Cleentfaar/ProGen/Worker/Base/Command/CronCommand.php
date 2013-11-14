<?php
namespace Cleentfaar\ProGen\Worker\Base\Command;

use Cleentfaar\ProGen\Worker\Base\Entity\Task;
use Cleentfaar\ProGen\Worker\Base\Exception\Exception;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Knp\Command\Command;

/**
 * The main command for use in a cronjob, fetches available tasks and executes them sequentially
 */
class CronCommand extends Command
{

    /**
     * @var \Doctrine\ORM\EntityManager
     */
    private $em;

    /**
     * @var array
     */
    private $config;

    protected function configure()
    {
        $this
            ->setName('cron')
            ->setDescription('Fetches available tasks for this worker and executes them sequentially, reporting back to the database where needed')
            ->addOption('log', null, InputOption::VALUE_REQUIRED, 'Here you can give a path to a log file to which this command should send it\'s debugging information')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            /**
             * Fetch available tasks for this worker
             */
            $this->config = $this->getSilexApplication()['progen'];
            $this->em = $this->getSilexApplication()['orm.em'];
            if (empty($this->config['task_types'])) {
                throw new Exception("No tasktypes configured for this worker");
            }
            $tasks = $this->em->getRepository('Cleentfaar\ProGen\Worker\Base\Entity\Task')->findAllForThisWorker($this->config['task_types']);
            if (empty($tasks)) {
                return $output->writeln("INFO: No tasks in database to execute (limited to type(s): ".implode(",", $this->config['task_types']).")");
            }
            foreach ($tasks as $task) {
                $this->executeTask($task, $output);
            }
        } catch (Exception $e) {
            throw new Exception("Failed to fetch tasks", $e->getCode(), $e);
        }
    }

    private function executeTask(Task $task, OutputInterface $output)
    {
        try {
            /**
             * Indicate to other workers that we are currently running the task,
             * preventing workers from taking eachothers tasks if the task takes a longer time
             */
            $task->setRunning(true);
            $task->setAttempts($task->getAttempts() + 1);
            $task->setQueued(false);
            $task->setDateExecutionStart(new \DateTime());
            $task->setDateExecutionEnd(null);
            $this->em->flush();

            if ($task->getDryRun() == true) {
                $output->writeln(sprintf("Safely dry-running task with ID %s", $task->getId()));
            }

            $failed = false;
            $failedReasons = array();
            $actions = array();
            $taskData = $task->getData(true);

            switch ($task->getType()) {
                case 'create-website':
                    if (!is_dir($this->config['websites_dir']) || !is_writable($this->config['websites_dir'])) {
                        throw new Exception("Websites directory (that should contain all website sources) does not exist or is not writable, check the configuration's value for 'websites_dir'");
                    }
                    $websitesDir = $this->config['websites_dir'];
                    if (!isset($taskData['hostname']) || $taskData['hostname'] == '') {
                        throw new Exception("No hostname was given");
                    }
                    $hostName = $taskData['hostname'];
                    $targetDir = $websitesDir . '/' . $hostName;
                    if ($task->getDryRun() == true) {
                        $actions[] = sprintf('Would have created directory for website in %s', $targetDir);
                    } else {
                        $actions[] = sprintf('Created directory for website in %s', $targetDir);
                        mkdir($targetDir, 0777);
                        chmod($targetDir, 0777);
                        chown($targetDir, $this->config['chown_user_website']);
                        chgrp($targetDir, $this->config['chown_group_website']);
                    }
                    break;
                case 'create-git-website':
                    if (!is_dir($this->config['git_websites_dir']) || !is_writable($this->config['git_websites_dir'])) {
                        throw new Exception("GIT websites directory (that should contain all cloned repositories) does not exist or is not writable, check the configuration's value for 'git_websites_dir'");
                    }
                    $gitWebsitesDir = $this->config['git_websites_dir'];
                    if (!isset($taskData['hostname']) || $taskData['hostname'] == '') {
                        throw new Exception("No hostname was given");
                    }
                    $hostName = $taskData['hostname'];
                    if (!isset($this->config['git_repositories_dir']) || $this->config['git_repositories_dir'] == '') {
                        throw new Exception("No repository directory was given, check the configuration's value for 'git_repositories_dir'");
                    }
                    $gitWebsiteDir = $gitWebsitesDir . '/' . $hostName;
                    $gitReposDir = $this->config['git_repositories_dir'];
                    $gitRepoName = $hostName.'.git';
                    $gitRepoDir = $gitReposDir.'/'.$hostName.'.git';
                    if (file_exists($gitRepoDir)) {
                        throw new Exception(sprintf("The attempted repository already exists on the filesystem (%s), did you perhaps retry this task without manually deleting earlier version?", $gitRepoDir));
                    }
                    $scriptToExecuteGitInit = sprintf("cd %s ; mkdir %s ; cd %s ; git init --shared --bare", $gitReposDir, $gitRepoName, $gitRepoName);
                    $scriptToExecuteGitClone = sprintf("cd %s ; git clone %s %s", $gitWebsitesDir, $gitRepoDir, $hostName);

                    if ($task->getDryRun() == true) {
                        $actions[] = sprintf('Would have executed the following command for creating a GIT repository: %s', $scriptToExecuteGitInit);
                        $actions[] = sprintf('Would have executed the following command for cloning the repository: %s', $scriptToExecuteGitClone);
                    } else {
                        shell_exec($scriptToExecuteGitInit);
                        $actions[] = sprintf('Executed the following command for creating a GIT repository: %s', $scriptToExecuteGitInit);
                        shell_exec($scriptToExecuteGitClone);
                        $actions[] = sprintf('Executed the following command for cloning the repository: %s', $scriptToExecuteGitClone);

                        chown($gitRepoDir, $this->config['chown_user_git_website']);
                        chgrp($gitRepoDir, $this->config['chown_group_git_website']);
                        chmod($gitRepoDir, 0777);

                        chown($gitWebsiteDir, $this->config['chown_user_git_website']);
                        chgrp($gitWebsiteDir, $this->config['chown_group_git_website']);
                        chmod($gitWebsiteDir, 0777);
                    }
                    break;
                case 'add-dns':
                    if (!file_exists($this->config['hosts_file']) || !is_writable($this->config['hosts_file'])) {
                        throw new Exception("Location of hosts file does not exist or is not writable, check the configuration's value for 'hosts_file'");
                    }
                    $hostsFile = $this->config['hosts_file'];
                    if (!isset($taskData['hostname']) || $taskData['hostname'] == '') {
                        throw new Exception("No hostname was given");
                    }
                    $hostName = $taskData['hostname'];
                    $dnsEntry = sprintf("10.0.2.3\t%s", $hostName);

                    if ($task->getDryRun() == true) {
                        $actions[] = sprintf('Would have written \'%s\' to hostfile in %s', $dnsEntry, $hostsFile);
                    } else {
                        $fh = fopen($hostsFile, 'a');
                        if ($fh === false) {
                            throw new Exception(sprintf("Failed to access dns hosts file in location %s, perhaps there are insufficient permissions to write to this location?", $hostsFile));
                        }
                        fwrite($fh, "\n");
                        fwrite($fh, $dnsEntry);
                        fwrite($fh, "\n");
                        fclose($fh);
                        $actions[] = sprintf('Written \'%s\' to hostfile in %s', $dnsEntry, $hostsFile);
                    }

                    break;
                case 'restart-apache':
                    $command = 'apache2ctl restart';
                    if ($task->getDryRun() == true) {
                        $actions[] = sprintf('Would have executed command \'%s\'', $command);
                    } else {
                        $output = shell_exec($command);
                        $actions[] = sprintf('Executed command \'%s\' with output: %s', $command, $output);
                    }
                    break;
                case 'restart-dns':
                    $command = '/etc/init.d/dnsmasq restart';
                    if ($task->getDryRun() == true) {
                        $actions[] = sprintf('Would have executed command \'%s\'', $command);
                    } else {
                        $output = shell_exec($command);
                        $actions[] = sprintf('Executed command \'%s\'', $command);
                        if (!stristr($output, 'Restarting DNS forwarder and DHCP server')) {
                            throw new Exception(sprintf("Failed to restart DNS server, output was: %s", $output));
                        }
                    }
                    break;
                default:
                    throw new Exception(sprintf("Unknown task type '%s' to run for this worker (make sure configuration and actual script's switch both use same tasks)", $task['type']));
                    break;
            }
        } catch (Exception $e) {
            $output->writeln(sprintf("TASK FAILED: %s", $e->getMessage()));
            $failed = true;
            $failedReasons[] = $e->getMessage();
        }

        /**
         * Indicate to other workers that we have finished the running task
         */
        $sql = "UPDATE `tasks` SET `executed` = 1, `running` = 0, `failed` = :failed, `failed_reasons` = :failed_reasons, `actions` = :actions, `date_execution_end` = :date_execution_end WHERE `id` = :id";
        $stmt = $db->prepare($sql);
        $stmt->execute(array('id' => $task['id'], 'failed' => $failed === true ? 1 : 0, 'failed_reasons' => json_encode($failedReasons), 'actions' => json_encode($actions), 'date_execution_end' => date("Y-m-d H:i:s")));

        if ($task->getDryRun() == false) {
            cli_write(sprintf("Task with ID %s and type '%s': %s", $task['id'], $task['type'], $failed === false ? "SUCCESSFUL" : "FAILED"));
        }
    }
}
