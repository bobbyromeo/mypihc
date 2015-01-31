<?php

use TiBeN\CrontabManager\CrontabRepository;
use TiBeN\CrontabManager\CrontabAdapter;

class CrontabRepositoryExtends extends CrontabRepository {
    public function __construct(CrontabAdapter $crontabAdapter)
    {
        parent::__construct($crontabAdapter);
        
    }
    /**
     * Finds jobs by matching theirs task comment with a regex
     *
     * @param String $regex
     * @throws InvalidArgumentException
     * @return Array of CronJobs
     */
    public function findJobByRegexComment($regex)
    {  
        /* Test if regex is valid */
        set_error_handler(function ($severity, $message, $file, $line) {
            throw new \Exception($message);
        });
        
        try {
            preg_match($regex, 'test');
            restore_error_handler();
        } catch (\Exception $e) {
            restore_error_handler();
            throw new \InvalidArgumentException('Not a valid Regex : ' . $e->getMessage());
            return;
        }

        $crontabJobs = array();
        $cj = $this->getJobs();
        if (!empty($cj)) {
            foreach ($cj as $crontabJob) {
                if (preg_match($regex, $crontabJob->comments)) {
                    array_push($crontabJobs, $crontabJob);
                }
            }
        }
        
        return $crontabJobs;
    }
}
?>