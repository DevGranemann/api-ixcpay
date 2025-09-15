<?php

namespace App\Service\Notification;

interface NotificationServiceInterface {

    public function notifyUser(string $email, string $subject, string $message): void;

    public function notifyTransfer(string $fromEmail, string $toEmail, float $amount, string $fromDocument, string $toDocument): void;

    public function notifyAccountOperation(string $email, string $operation, float $amount, string $document): void;
}
