<?php

namespace App\EventSubscriber;

use App\Document\LogEntry;
use App\Entity\User;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;
use Symfony\Component\Security\Http\Event\LogoutEvent;
use Symfony\Component\Security\Http\Event\LoginFailureEvent;


class LogoutLoggerSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly DocumentManager $documentManager,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            LogoutEvent::class => 'onLogout',
        ];
    }

    public function onLogout(LogoutEvent $event): void
    {
        $token = $event->getToken();
        $userId = null;

        if ($token) {
            $user = $token->getUser();
            if ($user instanceof User) {
                $userId = $user->getId();
            }
        }

        $log = new LogEntry($userId, 'logout', 'success');
        $this->documentManager->persist($log);
        $this->documentManager->flush();
    }
}
