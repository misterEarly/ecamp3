<?php

namespace App\Tests\DataPersister;

use App\DataPersister\InvitationDataPersister;
use App\DataPersister\Util\DataPersisterObservable;
use App\DTO\Invitation;
use App\Entity\CampCollaboration;
use App\Entity\User;
use App\Repository\CampCollaborationRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use stdClass;
use Symfony\Component\Security\Core\Security;

/**
 * @internal
 */
class InvitationDataPersisterTest extends TestCase {
    public const INVITEKEY = 'inviteKey';

    private Invitation $invitation;
    private CampCollaboration $campCollaboration;
    private User $user;

    private MockObject|DataPersisterObservable $dataPersisterObservable;
    private MockObject|CampCollaborationRepository $collaborationRepository;
    private MockObject|Security $security;
    private InvitationDataPersister $dataPersister;

    /**
     * @throws \ReflectionException
     */
    protected function setUp(): void {
        $this->invitation = new Invitation(self::INVITEKEY, '', '', '', false);
        $this->campCollaboration = new CampCollaboration();
        $this->user = new User();

        $this->dataPersisterObservable = $this->createMock(DataPersisterObservable::class);
        $this->collaborationRepository = $this->createMock(CampCollaborationRepository::class);
        $this->security = $this->createMock(Security::class);
        $this->dataPersister = new InvitationDataPersister(
            $this->dataPersisterObservable,
            $this->collaborationRepository,
            $this->security
        );
    }

    public function testSupportsInvitationsAlsoIfDataPersisterObservableDoesNotSupportIt() {
        $this->dataPersisterObservable->expects(self::never())->method('supports')->willReturn(false);

        self::assertThat($this->dataPersister->supports($this->invitation), self::isTrue());
    }

    public function doesNotSupportAnythingElseThanInvitations() {
        $this->dataPersisterObservable->expects(self::never())->method('supports')->willReturn(false);

        self::assertThat($this->dataPersister->supports(new stdClass()), self::isFalse());
        self::assertThat($this->dataPersister->supports(null), self::isFalse());
    }

    public function testUpdatesInvitationCorrectlyOnAccept() {
        $this->collaborationRepository
            ->expects(self::once())
            ->method('findByInviteKey')
            ->with(self::INVITEKEY)
            ->willReturn($this->campCollaboration)
        ;
        $this->security->expects(self::once())->method('getUser')->willReturn($this->user);

        $result = $this->dataPersister->onAccept($this->invitation);

        self::assertThat($result->user, self::equalTo($this->user));
        self::assertThat($result->status, self::equalTo(CampCollaboration::STATUS_ESTABLISHED));
        self::assertThat($result->inviteKey, self::isNull());
        self::assertThat($result->inviteEmail, self::isNull());
    }

    public function testUpdatesInvitationCorrectlyOnReject() {
        $this->collaborationRepository
            ->expects(self::once())
            ->method('findByInviteKey')
            ->with(self::INVITEKEY)
            ->willReturn($this->campCollaboration)
        ;
        $this->security->expects(self::never())->method('getUser');

        $result = $this->dataPersister->onReject($this->invitation);

        self::assertThat($result->status, self::equalTo(CampCollaboration::STATUS_INACTIVE));
        self::assertThat($result->inviteKey, self::isNull());
        self::assertThat($result->inviteEmail, self::equalTo($this->campCollaboration->inviteEmail));
        self::assertThat($result->user, self::equalTo($this->campCollaboration->user));
    }
}
