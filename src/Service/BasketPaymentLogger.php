<?php

namespace App\Service;

use App\Document\LogEntry;
use App\Entity\Basket;
use App\Entity\User;
use Doctrine\ODM\MongoDB\DocumentManager;

final class BasketPaymentLogger
{
    public function __construct(
        private readonly DocumentManager $documentManager,
    ) {
    }

    public function log(Basket $basket, bool $success, ?\Throwable $exception = null): void
    {
        $user = $basket->getUser();
        $userId = $user instanceof User ? $user->getId() : null;

        $details = sprintf(
            'basketId=%s userId=%s reservationCount=%d basketStatus=%s isActive=%s',
            $basket->getId() ?? 'null',
            $userId ?? 'null',
            $basket->getReservations()->count(),
            $basket->getStatus() ?? 'null',
            $basket->isActive() ? 'true' : 'false'
        );

        if (!$success && $exception) {
            $details .= sprintf(' error=%s', $exception::class);
        }

        $log = new LogEntry(
            $userId,
            'Basket paid',
            $success ? 'success' : 'failed',
            $details
        );

        $this->documentManager->persist($log);
        $this->documentManager->flush();
    }
}