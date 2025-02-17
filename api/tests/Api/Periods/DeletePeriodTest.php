<?php

namespace App\Tests\Api\Periods;

use App\Entity\Period;
use App\Tests\Api\ECampApiTestCase;

/**
 * @internal
 */
class DeletePeriodTest extends ECampApiTestCase {
    public function testDeletePeriodIsDeniedForAnonymousUser() {
        $period = static::$fixtures['period1'];
        static::createBasicClient()->request('DELETE', '/periods/'.$period->getId());
        $this->assertResponseStatusCodeSame(401);
        $this->assertJsonContains([
            'code' => 401,
            'message' => 'JWT Token not found',
        ]);
    }

    public function testDeletePeriodIsDeniedForUnrelatedUser() {
        $period = static::$fixtures['period1'];
        static::createClientWithCredentials(['username' => static::$fixtures['user4unrelated']->getUsername()])
            ->request('DELETE', '/periods/'.$period->getId())
        ;

        $this->assertResponseStatusCodeSame(404);
        $this->assertJsonContains([
            'title' => 'An error occurred',
            'detail' => 'Not Found',
        ]);
    }

    public function testDeletePeriodIsDeniedForInactiveCollaborator() {
        $period = static::$fixtures['period1'];
        static::createClientWithCredentials(['username' => static::$fixtures['user5inactive']->getUsername()])
            ->request('DELETE', '/periods/'.$period->getId())
        ;

        $this->assertResponseStatusCodeSame(404);
        $this->assertJsonContains([
            'title' => 'An error occurred',
            'detail' => 'Not Found',
        ]);
    }

    public function testDeletePeriodIsDeniedForGuest() {
        $period = static::$fixtures['period1'];
        static::createClientWithCredentials(['username' => static::$fixtures['user3guest']->getUsername()])
            ->request('DELETE', '/periods/'.$period->getId())
        ;

        $this->assertResponseStatusCodeSame(403);
        $this->assertJsonContains([
            'title' => 'An error occurred',
            'detail' => 'Access Denied.',
        ]);
    }

    public function testDeletePeriodIsAllowedForMember() {
        $period = static::$fixtures['period1'];
        static::createClientWithCredentials(['username' => static::$fixtures['user2member']->getUsername()])
            ->request('DELETE', '/periods/'.$period->getId())
        ;
        $this->assertResponseStatusCodeSame(204);
        $this->assertNull($this->getEntityManager()->getRepository(Period::class)->find($period->getId()));
    }

    public function testDeletePeriodIsAllowedForManager() {
        $period = static::$fixtures['period1'];
        static::createClientWithCredentials()->request('DELETE', '/periods/'.$period->getId());
        $this->assertResponseStatusCodeSame(204);
        $this->assertNull($this->getEntityManager()->getRepository(Period::class)->find($period->getId()));
    }

    public function testDeletePeriodFromCampPrototypeIsDeniedForUnrelatedUser() {
        $period = static::$fixtures['period1campPrototype'];
        static::createClientWithCredentials()->request('DELETE', '/periods/'.$period->getId());

        $this->assertResponseStatusCodeSame(403);
        $this->assertJsonContains([
            'title' => 'An error occurred',
            'detail' => 'Access Denied.',
        ]);
    }

    public function testDeleteLastPeriodIsRejected() {
        $period = static::$fixtures['period1camp2'];
        static::createClientWithCredentials()
            ->request('DELETE', '/periods/'.$period->getId())
        ;
        $this->assertResponseStatusCodeSame(422);
        $this->assertJsonContains([
            'violations' => [
                [
                    'propertyPath' => 'camp.periods',
                    'message' => 'A camp must have at least one period.',
                ],
            ],
        ]);
    }
}
