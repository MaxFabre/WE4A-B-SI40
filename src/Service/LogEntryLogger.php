<?php

namespace App\Service;

use App\Document\LogEntry;
use Doctrine\ODM\MongoDB\DocumentManager;

final class LogEntryLogger
{
    public function __construct(
        private readonly DocumentManager $documentManager,
    ) {
    }

    /**
     * @param array<string, scalar|array|null> $details
     */
    public function log(?int $userId, string $type, string $status, array $details = []): void
    {
        $log = new LogEntry(
            $userId,
            $type,
            $status,
            $this->formatDetails($details) ?: null
        );

        $this->documentManager->persist($log);
        $this->documentManager->flush();
    }

    /**
     * @param array<string, scalar|array|null> $details
     */
    private function formatDetails(array $details): string
    {
        $parts = [];

        foreach ($details as $key => $value) {
            if (is_array($value)) {
                $value = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            } elseif (is_bool($value)) {
                $value = $value ? 'true' : 'false';
            } elseif ($value === null) {
                $value = 'null';
            }

            $parts[] = $key.'='.$value;
        }

        return implode(' | ', $parts);
    }
}