<?php

namespace App\GangSheet\Traits;

use App\Jobs\SubmitLogJob;

trait HasLog
{
    private string $log = '';

    public function __destruct()
    {
        $this->submitLog();
    }

    public function getLogPath(): string
    {
        $date = now()->format('Y-m-d');
        return "logs/default/{$date}.log";
    }

    public function addLog($message, $data = null, $report = false): void
    {
        if (!is_string($message)) {
            $message = json_encode($message);
        }

        $time = now()->format('Y-m-d H:i:s');
        $server = strtoupper(config('app.server_name'));

        $this->log = "[{$time}][{$server}]-{$message}" . PHP_EOL . $this->log;

        if (php_sapi_name() === "cli") {
            printf("%s\n", $message);
        }

        if ($report) {
            slack_report($message);
        }

        if ($data) {
            $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            $customIndentedJson = preg_replace('/^/m', '    ', $json);
            $this->log = $customIndentedJson . PHP_EOL . $this->log;
        }

        if (substr_count($this->log, PHP_EOL) > 100) {
            $this->submitLog();
        }
    }

    public function clearLog(): void
    {
        $logPath = $this->getLogPath();
        spaces()->delete($logPath);
    }

    public function getLog(): array
    {
        $logPath = $this->getLogPath();
        return [
            'log_file_path' => spaces()->url($logPath),
            'log_content' => spaces()->get($logPath) ?? ''
        ];
    }

    public function addLogSeparator(): void
    {
        $separator = "\n\n";
        $separator .= str_repeat('=', 255);
        $separator .= "\n\n";

        $this->log = $separator . $this->log;

        if (substr_count($this->log, PHP_EOL) > 100) {
            $this->submitLog();
        }
    }

    private function submitLog(): void
    {
        if ($this->log) {
            $logPath = $this->getLogPath();
            SubmitLogJob::dispatch($logPath, $this->log);
            $this->log = '';
        }
    }
}
