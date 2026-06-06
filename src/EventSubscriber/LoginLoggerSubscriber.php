<?php

namespace App\EventSubscriber;

use App\Document\LogEntry;
use App\Entity\User;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;
use Symfony\Component\Security\Http\Event\LoginFailureEvent;


class LoginLoggerSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly DocumentManager $documentManager,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            LoginSuccessEvent::class => 'onLoginSuccess',
            LoginFailureEvent::class => 'onLoginFailure'
        ];
    }

    public function onLoginSuccess(LoginSuccessEvent $event): void
    {
        $user = $event->getUser();

        if (!$user instanceof User) {
            return;
        }

        $log = new LogEntry(
            $user->getId(),
            'login',
            'success'
        );

        $this->documentManager->persist($log);
        $this->documentManager->flush();
    }

    public function onLoginFailure(LoginFailureEvent $event): void
    {
        $userId = null;
        $username = null;
        $exception = $event->getException();
        $token = $exception->getToken() ?? null;

        if ($token) {
            $user = $token->getUser();
            if ($user instanceof User) {
                $userId = $user->getId();
            } elseif (is_string($user)) {
                $username = $user;
            }
        }

        $log = new LogEntry($userId, 'login', 'failure');
        if ($username) {
            $log->setStatus('failure:'.$username);
        }

        $this->documentManager->persist($log);
        $this->documentManager->flush();
    }
}
