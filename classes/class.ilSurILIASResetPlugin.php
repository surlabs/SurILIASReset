<?php

declare(strict_types=1);

class ilSurILIASResetPlugin extends ilCronHookPlugin
{
    public const PLUGIN_NAME = "SurILIASReset";
    private static $instance;

    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function getCronJobInstance($jobId): ilCronJob
    {
        if ($jobId === ilSurILIASResetCron::ID) {
            return new ilSurILIASResetCron();
        }

        throw new OutOfBoundsException("No cron job found with ID: " . $jobId);
    }

    public function getCronJobInstances(): array
    {
        return [new ilSurILIASResetCron()];
    }

    public function getPluginName(): string
    {
        return self::PLUGIN_NAME;
    }
}