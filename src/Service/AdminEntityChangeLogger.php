<?php

namespace App\Service;

use App\Entity\User;

final class AdminEntityChangeLogger
{
    public function __construct(
        private readonly LogEntryLogger $logEntryLogger,
    ) {
    }

    /**
     * @param array<string, scalar|array|null> $newData
     */
    public function log(?User $admin, string $action, string $entity, array $newData): void
    {
        try {
            $this->logEntryLogger->log(
                $admin?->getId(),
                'Admin entity change',
                'success',
                [
                    'date' => (new \DateTimeImmutable('now', new \DateTimeZone('Europe/Paris')))->format(DATE_ATOM),
                    'admin_id' => $admin?->getId(),
                    'admin_username' => $admin?->getUsername(),
                    'action' => $action,
                    'entity' => $entity,
                    'new_data' => $newData,
                    'source' => 'admin_form',
                ]
            );
        } catch (\Throwable) {
        }
    }
}
