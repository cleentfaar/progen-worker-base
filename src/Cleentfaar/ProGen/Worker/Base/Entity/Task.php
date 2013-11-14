<?php
namespace Cleentfaar\ProGen\Worker\Base\Entity;

use Cleentfaar\ProGen\Worker\Base\Exception\Exception;
use Doctrine\ORM\Mapping as ORM;

/**
 * Task
 *
 * @ORM\Table(name="tasks")
 * @ORM\Entity(repositoryClass="Cleentfaar\ProGen\Worker\Base\Entity\Repository\TaskRepository")
 * @ORM\HasLifecycleCallbacks()
 */
class Task
{
    /**
     * @var string
     *
     * @ORM\Column(name="type", type="text", nullable=false)
     */
    private $type;

    /**
     * @var array
     *
     * @ORM\Column(name="data", type="text", nullable=true)
     */
    private $data;

    /**
     * @var boolean
     *
     * @ORM\Column(name="dry_run", type="boolean", nullable=false)
     */
    private $dryRun;

    /**
     * @var boolean
     *
     * @ORM\Column(name="executed", type="boolean", nullable=false)
     */
    private $executed;

    /**
     * @var boolean
     *
     * @ORM\Column(name="running", type="boolean", nullable=false)
     */
    private $running;

    /**
     * @var boolean
     *
     * @ORM\Column(name="failed", type="boolean", nullable=false)
     */
    private $failed;

    /**
     * @var string
     *
     * @ORM\Column(name="failed_reasons", type="text", nullable=true)
     */
    private $failedReasons;

    /**
     * @var boolean
     *
     * @ORM\Column(name="queued", type="boolean", nullable=false)
     */
    private $queued;

    /**
     * @var string
     *
     * @ORM\Column(name="actions", type="text", nullable=true)
     */
    private $actions;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="date_created", type="datetime", nullable=false)
     */
    private $dateCreated;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="date_updated", type="datetime", nullable=true)
     */
    private $dateUpdated;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="date_execution_start", type="datetime", nullable=true)
     */
    private $dateExecutionStart;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="date_execution_end", type="datetime", nullable=true)
     */
    private $dateExecutionEnd;

    /**
     * @var integer
     *
     * @ORM\Column(name="attempts", type="integer")
     */
    private $attempts;

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    public function __construct()
    {
        $this->data = new \stdClass();
        $this->attempts = 0;
        $this->dryRun = false;
        $this->queued = true;
        $this->executed = false;
        $this->running = false;
        $this->failed = false;
    }

    /**
     * Set type
     *
     * @param string $type
     * @return Task
     */
    public function setType($type)
    {
        $this->type = $type;
    
        return $this;
    }

    /**
     * Get type
     *
     * @return string 
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set data
     *
     * @param \stdClass $data
     * @return Task
     */
    public function setData(\stdClass $data)
    {
        $this->data = $data;
    
        return $this;
    }

    /**
     * Get data
     *
     * @return \stdClass
     */
    public function getData($asArray = false)
    {
    	if ($asArray === true) {
    		return (array) $this->data;
    	}
        return $this->data;
    }

    /**
     * Set dryRun
     *
     * @param boolean $dryRun
     * @return Task
     */
    public function setDryRun($dryRun)
    {
        $this->dryRun = $dryRun;
    
        return $this;
    }

    /**
     * Get dryRun
     *
     * @return boolean 
     */
    public function getDryRun()
    {
        return $this->dryRun;
    }

    /**
     * Set executed
     *
     * @param boolean $executed
     * @return Task
     */
    public function setExecuted($executed)
    {
        $this->executed = $executed;
    
        return $this;
    }

    /**
     * Get executed
     *
     * @return boolean 
     */
    public function getExecuted()
    {
        return $this->executed;
    }

    /**
     * Set running
     *
     * @param boolean $running
     * @return Task
     */
    public function setRunning($running)
    {
        $this->running = $running;
    
        return $this;
    }

    /**
     * Get running
     *
     * @return boolean 
     */
    public function getRunning()
    {
        return $this->running;
    }

    /**
     * Set failed
     *
     * @param boolean $failed
     * @return Task
     */
    public function setFailed($failed)
    {
        $this->failed = $failed;
    
        return $this;
    }

    /**
     * Get failed
     *
     * @return boolean 
     */
    public function getFailed()
    {
        return $this->failed;
    }

    /**
     * Set failedReasons
     *
     * @param string $failedReasons
     * @return Task
     */
    public function setFailedReasons($failedReasons)
    {
        $this->failedReasons = $failedReasons;
    
        return $this;
    }

    /**
     * Get failedReasons
     *
     * @return string 
     */
    public function getFailedReasons()
    {
        return $this->failedReasons;
    }

    /**
     * Set actions
     *
     * @param string $actions
     * @return Task
     */
    public function setActions($actions)
    {
        $this->actions = $actions;
    
        return $this;
    }

    /**
     * Get actions
     *
     * @return string 
     */
    public function getActions()
    {
        return $this->actions;
    }

    /**
     * Set dateCreated
     *
     * @param \DateTime $dateCreated
     * @return Project
     */
    public function setDateCreated($dateCreated)
    {
        $this->dateCreated = $dateCreated;

        return $this;
    }

    /**
     * Get dateCreated
     *
     * @return \DateTime
     */
    public function getDateCreated()
    {
        return $this->dateCreated;
    }

    /**
     * Set dateUpdated
     *
     * @param \DateTime $dateUpdated
     * @return Project
     */
    public function setDateUpdated($dateUpdated)
    {
        $this->dateUpdated = $dateUpdated;

        return $this;
    }

    /**
     * Get dateUpdated
     *
     * @return \DateTime
     */
    public function getDateUpdated()
    {
        return $this->dateUpdated;
    }

    /**
     * Set dateExecutionStart
     *
     * @param \DateTime $dateExecutionStart
     * @return Task
     */
    public function setDateExecutionStart($dateExecutionStart)
    {
        $this->dateExecutionStart = $dateExecutionStart;
    
        return $this;
    }

    /**
     * Get dateExecutionStart
     *
     * @return \DateTime 
     */
    public function getDateExecutionStart()
    {
        return $this->dateExecutionStart;
    }

    /**
     * Set dateExecutionEnd
     *
     * @param \DateTime $dateExecutionEnd
     * @return Task
     */
    public function setDateExecutionEnd($dateExecutionEnd)
    {
        $this->dateExecutionEnd = $dateExecutionEnd;
    
        return $this;
    }

    /**
     * Get dateExecutionEnd
     *
     * @return \DateTime 
     */
    public function getDateExecutionEnd()
    {
        return $this->dateExecutionEnd;
    }

    /**
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @ORM\PrePersist
     */
    public function prePersist()
    {
        throw new Exception("Workers can not create new tasks");
    }

    /**
     * @ORM\PreUpdate
     */
    public function preUpdate()
    {
        $this->dateUpdated = new \DateTime();
        $this->data = json_encode($this->data);
    	$this->actions = json_encode($this->actions);
    	$this->failedReasons = json_encode($this->failedReasons);
    }

    /**
     * @ORM\PostLoad
     */
    public function postLoad()
    {
    	$this->data = json_decode($this->data);
    	$this->actions = json_decode($this->actions);
    	$this->failedReasons = json_decode($this->failedReasons);
    }

    /**
     * Set attempts
     *
     * @param integer $attempts
     * @return Task
     */
    public function setAttempts($attempts)
    {
        $this->attempts = $attempts;
    
        return $this;
    }

    /**
     * Get attempts
     *
     * @return integer 
     */
    public function getAttempts()
    {
        return $this->attempts;
    }

    /**
     * Set queued
     *
     * @param boolean $queued
     * @return Task
     */
    public function setQueued($queued)
    {
        $this->queued = $queued;
    
        return $this;
    }

    /**
     * Get queued
     *
     * @return boolean 
     */
    public function getQueued()
    {
        return $this->queued;
    }
}