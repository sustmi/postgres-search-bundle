<?php

namespace Intaro\PostgresSearchBundle\EventListener;

use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Common\Annotations\AnnotationException;
use Doctrine\Common\Persistence\Mapping\ClassMetadata;

class SearchListener
{
    public function onFlush(OnFlushEventArgs $args)
    {
        $em = $args->getEntityManager();
        $uow = $em->getUnitOfWork();
        $changedEntities = [];

        foreach ($uow->getScheduledEntityInsertions() as $entity) {
            $metadata = $em->getClassMetadata(get_class($entity));
            foreach ($metadata->getFieldNames() as $field) {
                if ($metadata->getTypeOfField($field) != 'tsvector') {
                    continue;
                }

                $fieldMapping = $metadata->getFieldMapping($field)['options'];
                if (!isset($fieldMapping['customSchemaOptions']['searchFields'])) {
                    continue;
                }

                $searchFields = $fieldMapping['customSchemaOptions']['searchFields'];
                $searchData = [];
                foreach ($searchFields as $searchField) {
                    $getter = 'get' . ucfirst($searchField);

                    if (!method_exists($entity, $getter)) {
                        throw new AnnotationException(
                            'Getter ' . $getter . ' for search field does not exists.'
                        );
                    }

                    $searchData[] = $entity->$getter();
                }

                $metadata->setFieldValue($entity, $field, $searchData);
                $uow->recomputeSingleEntityChangeSet($metadata, $entity);
            }
        }

        foreach ($uow->getScheduledEntityUpdates() as $entity) {
            $changeSet = $uow->getEntityChangeSet($entity);
            $updateNeeded = false;

            $metadata = $em->getClassMetadata(get_class($entity));
            foreach ($metadata->getFieldNames() as $field) {
                if ($metadata->getTypeOfField($field) != 'tsvector') {
                    continue;
                }

                $fieldMapping = $metadata->getFieldMapping($field)['options'];
                if (!isset($fieldMapping['customSchemaOptions']['searchFields'])) {
                    continue;
                }

                $updateNeeded = false;
                $searchFields = $fieldMapping['customSchemaOptions']['searchFields'];
                foreach ($changeSet as $fieldName => $value) {
                    if (in_array($fieldName, $searchFields)) {
                        $updateNeeded = true;
                        break;
                    }
                }

                if (!$updateNeeded) {
                    continue;
                }

                $searchData = [];
                foreach ($searchFields as $searchField) {
                    $getter = 'get' . ucfirst($searchField);

                    if (!method_exists($entity, $getter)) {
                        throw new AnnotationException(
                            'Getter ' . $getter . ' for search field does not exists.'
                        );
                    }

                    $searchData[] = $entity->$getter();
                }

                $metadata->setFieldValue($entity, $field, $searchData);
                $uow->recomputeSingleEntityChangeSet($metadata, $entity);

            }
        }
    }
}
