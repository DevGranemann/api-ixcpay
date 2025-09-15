<?php

namespace App\Service\Notification;

class MockNotificationService implements NotificationServiceInterface
{
    private string $logFile;

    public function __construct(?string $logFile = null)
    {
        $baseDir = dirname(__DIR__, 3); // src/Service/Notification -> project root
        $default = $baseDir . DIRECTORY_SEPARATOR . 'var' . DIRECTORY_SEPARATOR . 'log' . DIRECTORY_SEPARATOR . 'notifications.log';
        $this->logFile = $logFile ?: $default;

        $dir = dirname($this->logFile);
        if (!is_dir($dir)) {
            @mkdir($dir, 0777, true);
        }
    }

    public function notifyUser(string $email, string $subject, string $message): void
    {
        $this->writeLine(sprintf('[%s] user=%s subject="%s" message="%s"',
            (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
            $email,
            $subject,
            $message
        ));
    }

    public function notifyTransfer(string $fromEmail, string $toEmail, float $amount, string $fromDocument, string $toDocument): void
    {
        $ts = (new \DateTimeImmutable())->format('Y-m-d H:i:s');
        $this->writeLine(sprintf('[%s] transfer: from=%s(%s) to=%s(%s) amount=%.2f',
            $ts,
            $fromEmail,
            $fromDocument,
            $toEmail,
            $toDocument,
            $amount
        ));
    }

    public function notifyAccountOperation(string $email, string $operation, float $amount, string $document): void
    {
        $ts = (new \DateTimeImmutable())->format('Y-m-d H:i:s');
        $this->writeLine(sprintf('[%s] %s: user=%s doc=%s amount=%.2f',
            $ts,
            strtolower($operation),
            $email,
            $document,
            $amount
        ));
    }

    private function writeLine(string $line): void
    {
        try {
            file_put_contents($this->logFile, $line . PHP_EOL, FILE_APPEND | LOCK_EX);
        } catch (\Throwable) {
            // é um mock
        }
    }
}
