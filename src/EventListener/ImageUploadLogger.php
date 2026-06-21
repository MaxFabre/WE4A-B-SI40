<?php

namespace App\EventListener;

use App\Entity\Person;
use Vich\UploaderBundle\Event\Event;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ORM\EntityManagerInterface;
use App\Document\ImageLog;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use App\Entity\User;

class ImageUploadLogger
{
    private string $projectDir;

    public function __construct(
        private DocumentManager $dm,
        private EntityManagerInterface $em,
        private LoggerInterface $logger,
        KernelInterface $kernel
    ) {
        $this->projectDir = $kernel->getProjectDir();
    }

    public function onPostUpload(Event $event): void
    {
        $object = $event->getObject();
        $mapping = $event->getMapping();

        // --- récupérer le nom final du fichier
        $fileNameProperty = null;
        if (method_exists($mapping, 'getFileNamePropertyName')) {
            $fileNameProperty = $mapping->getFileNamePropertyName();
        } elseif (method_exists($mapping, 'getFileNameProperty')) {
            $fileNameProperty = $mapping->getFileNameProperty();
        }

        $storedFileName = null;
        if ($fileNameProperty) {
            $getter = 'get' . ucfirst($fileNameProperty);
            if (method_exists($object, $getter)) {
                $storedFileName = $object->$getter();
            } elseif (property_exists($object, $fileNameProperty)) {
                $storedFileName = $object->{$fileNameProperty};
            }
        }
        if (!$storedFileName) {
            foreach (['getImageName','getFilename','getFileName'] as $g) {
                if (method_exists($object, $g)) {
                    $storedFileName = $object->$g();
                    break;
                }
            }
        }

        $uriPrefix = method_exists($mapping, 'getUriPrefix') ? $mapping->getUriPrefix() : null;
        $path = $storedFileName ? rtrim((string)$uriPrefix, '/') . '/' . ltrim($storedFileName, '/') : (string)($uriPrefix ?? '');
        $format = $storedFileName ? pathinfo($storedFileName, PATHINFO_EXTENSION) : '';

        // taille sur disque
        $size = '';
        $uploadDir = null;
        if (method_exists($mapping, 'getUploadDestination')) {
            $uploadDir = $mapping->getUploadDestination();
        }
        if (!$uploadDir && $uriPrefix) {
            $uploadDir = $this->projectDir . '/public' . $uriPrefix;
        }
        if ($uploadDir && $storedFileName) {
            $fullPath = rtrim($uploadDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $storedFileName;
            if (file_exists($fullPath)) {
                $size = (string) filesize($fullPath);
            }
        }

        // --- qui a uploadé
        $uploader = $this->resolveUploader($object);

        // --- cible film ou person
        $film = $this->resolveRelation($object, 'getFilm', ['getId','getTitle']);
        $person = $this->resolveRelation($object, 'getPerson', ['getId','getFirstname','getLastname']);

        $userCategory = null;
        if ($this->isInstanceOf($object, 'App\\Entity\\Person')) {
            $person = $this->extractEntityInfo($object, ['getId','getFirstname','getLastname']);

            // PRIORITE 1 : si $uploader est déjà résolu (array), l'utiliser comme user
            if (!empty($uploader) && is_array($uploader) && isset($uploader['id'])) {
                $userCategory = $uploader;
            }

            // PRIORITE 2 : si la Person expose getUploadedBy(), utiliser cette valeur
            if ($userCategory === null && method_exists($object, 'getUploadedBy')) {
                $maybeOwner = $object->getUploadedBy();
                if (is_object($maybeOwner)) {
                    $userCategory = $this->extractUserInfo($maybeOwner);
                } elseif ($maybeOwner !== null) {
                    $userCategory = ['id' => $maybeOwner];
                }
            }

            // PRIORITE 3 : fallback repository si toujours rien
            if ($userCategory === null) {
                $owner = $this->findUserByPerson($object);
                if ($owner) {
                    $userCategory = $this->extractUserInfo($owner);
                }
            }
        } else {
            // si on a une relation person (upload sur autre entité)
            if ($person) {
                $personEntity = $this->tryResolveEntityFromRelation($object, 'getPerson');
                if ($personEntity) {
                    if (method_exists($personEntity, 'getUploadedBy')) {
                        $maybeOwner = $personEntity->getUploadedBy();
                        if (is_object($maybeOwner)) {
                            $userCategory = $this->extractUserInfo($maybeOwner);
                        } elseif ($maybeOwner !== null) {
                            $userCategory = ['id' => $maybeOwner];
                        }
                    }
                    if ($userCategory === null) {
                        $owner = $this->findUserByPerson($personEntity);
                        if ($owner) {
                            $userCategory = $this->extractUserInfo($owner);
                        }
                    }
                }
            }
        }




        // si l'objet est un Film, exposer ses infos
        if ($this->isInstanceOf($object, 'App\\Entity\\Film')) {
            $film = $this->extractEntityInfo($object, ['getId','getTitle']);
        }

        // debug
        $this->logger->debug('ImageUploadLogger debug', [
            'object_class' => is_object($object) ? get_class($object) : gettype($object),
            'storedFileName' => $storedFileName,
            'uploader' => $uploader,
            'user_category' => $userCategory,
            'film' => $film,
            'person' => $person,
        ]);

        $data = [
            'path' => $path,
            'name' => $storedFileName ?? '',
            'format' => $format,
            'size' => $size,
            'uploader' => $uploader,
            'user' => $userCategory,
            'film' => $film,
            'person' => $person,
            'created_at' => (new \DateTimeImmutable())->format(\DateTime::ATOM),
        ];

// Si on a user, on ne veut pas stocker person
        if (!empty($data['user'])) {
            $data['person'] = null;
        }

        $this->logger->debug('Image log payload', $data);

        $log = new ImageLog($data);
        $this->dm->persist($log);
        $this->dm->flush();

    }

    private function resolveUploader(object $obj): array
    {
        $candidates = ['getUploadedBy','getUser','getOwner'];
        foreach ($candidates as $m) {
            if (method_exists($obj, $m)) {
                $u = $obj->$m();
                if (is_object($u)) {
                    return $this->extractUserInfo($u);
                }
                if ($u !== null) {
                    return ['id' => $u];
                }
            }
        }
        if ($this->looksLikeUser($obj)) {
            return $this->extractUserInfo($obj);
        }
        return [];
    }

    private function extractUserInfo(object $u): array
    {
        $id = method_exists($u, 'getId') ? $u->getId() : (method_exists($u, 'getUserIdentifier') ? $u->getUserIdentifier() : null);
        $firstname = method_exists($u, 'getFirstname') ? $u->getFirstname() : (method_exists($u, 'getFirstName') ? $u->getFirstName() : null);
        $lastname = method_exists($u, 'getLastname') ? $u->getLastname() : (method_exists($u, 'getLastName') ? $u->getLastName() : null);

        $out = array_filter([
            'id' => $id,
            'firstname' => $firstname,
            'lastname' => $lastname,
        ], fn($v) => $v !== null && $v !== '');

        if (isset($out['id'])) {
            $out['id'] = is_object($out['id']) ? (string)$out['id'] : $out['id'];
        }
        return $out;
    }

    private function resolveRelation(object $obj, string $method, array $wantedGetters): ?array
    {
        if (!method_exists($obj, $method)) {
            return null;
        }
        $rel = $obj->$method();
        if (!$rel || !is_object($rel)) {
            return null;
        }
        return $this->extractEntityInfo($rel, $wantedGetters);
    }

    private function extractEntityInfo(object $rel, array $wantedGetters): ?array
    {
        $out = [];
        foreach ($wantedGetters as $g) {
            if (method_exists($rel, $g)) {
                $key = lcfirst(substr($g, 3));
                $out[$key] = $rel->$g();
            }
        }
        return $out ?: null;
    }

    private function looksLikeUser(object $obj): bool
    {
        return method_exists($obj, 'getId') && (method_exists($obj, 'getFirstname') || method_exists($obj, 'getUserIdentifier'));
    }

    private function isInstanceOf(object $obj, string $fqcn): bool
    {
        return class_exists($fqcn) && $obj instanceof $fqcn;
    }

    private function findUserByPerson(Person $person)
    {
        try {
            // utilise la méthode que tu as ajoutée dans UserRepository
            $repo = $this->em->getRepository(\App\Entity\User::class);
            if (method_exists($repo, 'findUserByPerson')) {
                return $repo->findUserByPerson($person);
            }

            // fallback : si la méthode n'existe pas, tenter un findOneBy classique
            if (method_exists($person, 'getId')) {
                $user = $repo->findOneBy(['person' => $person]);
                if ($user) {
                    return $user;
                }
            }
        } catch (\Throwable $e) {
            $this->logger->debug('findUserByPerson failed: '.$e->getMessage());
        }
        return null;
    }


    private function tryResolveEntityFromRelation(object $obj, string $method)
    {
        if (!method_exists($obj, $method)) {
            return null;
        }
        $rel = $obj->$method();
        return is_object($rel) ? $rel : null;
    }
}
