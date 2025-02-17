<?php

namespace App\Tests\Api\Categories;

use App\Tests\Api\ECampApiTestCase;

/**
 * @internal
 */
class UpdateCategoryTest extends ECampApiTestCase {
    // TODO input filter tests
    // TODO validation tests

    public function testPatchCategoryIsDeniedForAnonymousUser() {
        $category = static::$fixtures['category1'];
        static::createBasicClient()->request('PATCH', '/categories/'.$category->getId(), ['json' => [
            'short' => 'LP',
            'name' => 'Lagerprogramm',
            'color' => '#FFFF00',
            'numberingStyle' => 'I',
            'preferredContentTypes' => [
                $this->getIriFor('contentTypeColumnLayout'),
                $this->getIriFor('contentTypeSafetyConcept'),
            ],
        ], 'headers' => ['Content-Type' => 'application/merge-patch+json']]);
        $this->assertResponseStatusCodeSame(401);
        $this->assertJsonContains([
            'code' => 401,
            'message' => 'JWT Token not found',
        ]);
    }

    public function testPatchCategoryIsDeniedForUnrelatedUser() {
        $category = static::$fixtures['category1'];
        static::createClientWithCredentials(['username' => static::$fixtures['user4unrelated']->getUsername()])
            ->request('PATCH', '/categories/'.$category->getId(), ['json' => [
                'short' => 'LP',
                'name' => 'Lagerprogramm',
                'color' => '#FFFF00',
                'numberingStyle' => 'I',
                'preferredContentTypes' => [
                    $this->getIriFor('contentTypeColumnLayout'),
                    $this->getIriFor('contentTypeSafetyConcept'),
                ],
            ], 'headers' => ['Content-Type' => 'application/merge-patch+json']])
        ;
        $this->assertResponseStatusCodeSame(404);
        $this->assertJsonContains([
            'title' => 'An error occurred',
            'detail' => 'Not Found',
        ]);
    }

    public function testPatchCategoryIsDeniedForInactiveCollaborator() {
        $category = static::$fixtures['category1'];
        static::createClientWithCredentials(['username' => static::$fixtures['user5inactive']->getUsername()])
            ->request('PATCH', '/categories/'.$category->getId(), ['json' => [
                'short' => 'LP',
                'name' => 'Lagerprogramm',
                'color' => '#FFFF00',
                'numberingStyle' => 'I',
                'preferredContentTypes' => [
                    $this->getIriFor('contentTypeColumnLayout'),
                    $this->getIriFor('contentTypeSafetyConcept'),
                ],
            ], 'headers' => ['Content-Type' => 'application/merge-patch+json']])
        ;
        $this->assertResponseStatusCodeSame(404);
        $this->assertJsonContains([
            'title' => 'An error occurred',
            'detail' => 'Not Found',
        ]);
    }

    public function testPatchCategoryIsDeniedForGuest() {
        $category = static::$fixtures['category1'];
        static::createClientWithCredentials(['username' => static::$fixtures['user3guest']->getUsername()])
            ->request('PATCH', '/categories/'.$category->getId(), ['json' => [
                'short' => 'LP',
                'name' => 'Lagerprogramm',
                'color' => '#FFFF00',
                'numberingStyle' => 'I',
                'preferredContentTypes' => [
                    $this->getIriFor('contentTypeColumnLayout'),
                    $this->getIriFor('contentTypeSafetyConcept'),
                ],
            ], 'headers' => ['Content-Type' => 'application/merge-patch+json']])
        ;
        $this->assertResponseStatusCodeSame(403);
        $this->assertJsonContains([
            'title' => 'An error occurred',
            'detail' => 'Access Denied.',
        ]);
    }

    public function testPatchCategoryIsAllowedForMember() {
        $category = static::$fixtures['category1'];
        $response = static::createClientWithCredentials(['username' => static::$fixtures['user2member']->getUsername()])
            ->request('PATCH', '/categories/'.$category->getId(), ['json' => [
                'short' => 'LP',
                'name' => 'Lagerprogramm',
                'color' => '#FFFF00',
                'numberingStyle' => 'I',
                'preferredContentTypes' => [
                    $this->getIriFor('contentTypeColumnLayout'),
                    $this->getIriFor('contentTypeSafetyConcept'),
                ],
            ], 'headers' => ['Content-Type' => 'application/merge-patch+json']])
        ;
        $this->assertResponseStatusCodeSame(200);
        $this->assertJsonContains([
            'short' => 'LP',
            'name' => 'Lagerprogramm',
            'color' => '#FFFF00',
            'numberingStyle' => 'I',
            '_links' => [
                'preferredContentTypes' => [
                    'href' => '/content_types?categories='.$this->getIriFor($category),
                ],
            ],
        ]);
    }

    public function testPatchCategoryIsAllowedForManager() {
        $category = static::$fixtures['category1'];
        $response = static::createClientWithCredentials()->request('PATCH', '/categories/'.$category->getId(), ['json' => [
            'short' => 'LP',
            'name' => 'Lagerprogramm',
            'color' => '#FFFF00',
            'numberingStyle' => 'I',
            'preferredContentTypes' => [
                $this->getIriFor('contentTypeColumnLayout'),
                $this->getIriFor('contentTypeSafetyConcept'),
            ],
        ], 'headers' => ['Content-Type' => 'application/merge-patch+json']]);
        $this->assertResponseStatusCodeSame(200);
        $this->assertJsonContains([
            'short' => 'LP',
            'name' => 'Lagerprogramm',
            'color' => '#FFFF00',
            'numberingStyle' => 'I',
            '_links' => [
                'preferredContentTypes' => [
                    'href' => '/content_types?categories='.$this->getIriFor($category),
                ],
            ],
        ]);
    }

    public function testPatchCategoryInCampPrototypeIsDeniedForUnrelatedUser() {
        $category = static::$fixtures['category1campPrototype'];
        $response = static::createClientWithCredentials()->request('PATCH', '/categories/'.$category->getId(), ['json' => [
            'short' => 'LP',
            'name' => 'Lagerprogramm',
            'color' => '#FFFF00',
            'numberingStyle' => 'I',
            'preferredContentTypes' => [
                $this->getIriFor('contentTypeColumnLayout'),
                $this->getIriFor('contentTypeSafetyConcept'),
            ],
        ], 'headers' => ['Content-Type' => 'application/merge-patch+json']]);
        $this->assertResponseStatusCodeSame(403);
        $this->assertJsonContains([
            'title' => 'An error occurred',
            'detail' => 'Access Denied.',
        ]);
    }

    public function testPatchCategoryDisallowsChangingCamp() {
        $category = static::$fixtures['category1'];
        static::createClientWithCredentials()->request('PATCH', '/categories/'.$category->getId(), ['json' => [
            'camp' => $this->getIriFor('camp2'),
        ], 'headers' => ['Content-Type' => 'application/merge-patch+json']]);

        $this->assertResponseStatusCodeSame(400);
        $this->assertJsonContains([
            'detail' => 'Extra attributes are not allowed ("camp" is unknown).',
        ]);
    }

    public function testPatchCategoryValidatesInvalidColor() {
        $category = static::$fixtures['category1'];
        static::createClientWithCredentials()->request('PATCH', '/categories/'.$category->getId(), ['json' => [
            'color' => 'green',
        ], 'headers' => ['Content-Type' => 'application/merge-patch+json']]);

        $this->assertResponseStatusCodeSame(422);
        $this->assertJsonContains([
            'violations' => [
                [
                    'propertyPath' => 'color',
                    'message' => 'This value is not valid.',
                ],
            ],
        ]);
    }

    public function testPatchCategoryValidatesInvalidNumberingStyle() {
        $category = static::$fixtures['category1'];
        static::createClientWithCredentials()->request('PATCH', '/categories/'.$category->getId(), ['json' => [
            'numberingStyle' => 'X',
        ], 'headers' => ['Content-Type' => 'application/merge-patch+json']]);

        $this->assertResponseStatusCodeSame(422);
        $this->assertJsonContains([
            'violations' => [
                [
                    'propertyPath' => 'numberingStyle',
                    'message' => 'The value you selected is not a valid choice.',
                ],
            ],
        ]);
    }
}
