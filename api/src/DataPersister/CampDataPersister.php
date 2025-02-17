<?php

namespace App\DataPersister;

use App\DataPersister\Util\AbstractDataPersister;
use App\DataPersister\Util\DataPersisterObservable;
use App\Entity\BaseEntity;
use App\Entity\Camp;
use App\Entity\CampCollaboration;
use App\Entity\User;
use App\Util\EntityMap;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Security;

class CampDataPersister extends AbstractDataPersister {
    public function __construct(
        DataPersisterObservable $dataPersisterObservable,
        private Security $security,
        private EntityManagerInterface $em,
    ) {
        parent::__construct(
            Camp::class,
            $dataPersisterObservable,
        );
    }

    /**
     * @param Camp $data
     */
    public function beforeCreate($data): BaseEntity {
        /** @var User $user */
        $user = $this->security->getUser();
        $data->creator = $user;
        $data->owner = $user;

        // copy from prototype, if given
        if (isset($data->campPrototype)) {
            $entityMap = new EntityMap();
            $data->copyFromPrototype($data->campPrototype, $entityMap);
        }

        return $data;
    }

    public function afterCreate($data): void {
        /** @var User $user */
        $user = $this->security->getUser();
        $collaboration = new CampCollaboration();
        $collaboration->user = $user;
        $collaboration->role = CampCollaboration::ROLE_MANAGER;
        $collaboration->status = CampCollaboration::STATUS_ESTABLISHED;
        $data->addCampCollaboration($collaboration);
        $this->em->persist($collaboration);
        $this->em->flush();
    }
}
