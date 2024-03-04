<?php

namespace RemoteTech\ComAxe\Client\Oauth;


use Doctrine\ORM\Mapping\Builder\ClassMetadataBuilder;
use Doctrine\Persistence\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Doctrine\Persistence\Mapping\Driver\MappingDriver;
use RemoteTech\ComAxe\Client\Oauth\Model\User;


class DoctrineDriver implements MappingDriver
{
//    public function __construct($className, ClassMetadata $metadata)
//    {
//        $this->loadMetadataForClass($className, $metadata);
//    }

    /**
     * @param $className
     * @param ClassMetadata $metadata
     * @return void
     * @requires
     */
    public function loadMetadataForClass($className, ClassMetadata $metadata)
    {
        die("sfdddas");
        (new ClassMetadataBuilder($metadata))
            ->setTable('user')
            ->createField('id', 'int')->makePrimaryKey()->length(10)->option('fixed', true)->build()
            ->createField('uuid', 'string')->length(32)->nullable(false)->unique(true)->build()
            ->createField('roles', 'string')->nullable(true)->build()
            ->addField('token', 'string')
            ->addField('refresh_token', 'string')
        ;
    }
    public function getAllClassNames(): array
    {
        return [User::class];
    }

    public function isTransient($className): bool
    {
        return false;
    }
}
