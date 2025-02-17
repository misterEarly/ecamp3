<?php

namespace App\Tests\Api\ContentNodes\SingleText;

use App\Entity\ContentNode\SingleText;
use App\Tests\Api\ContentNodes\CreateContentNodeTestCase;

/**
 * @internal
 */
class CreateSingleTextTest extends CreateContentNodeTestCase {
    public function setUp(): void {
        parent::setUp();

        $this->endpoint = '/content_node/single_texts';
        $this->entityClass = SingleText::class;
        $this->defaultContentType = static::$fixtures['contentTypeNotes'];
    }

    public function testCreateSingleTextFromString() {
        // given
        $text = 'TestText';

        // when
        $this->create($this->getExampleWritePayload(['text' => $text]));

        // then
        $this->assertResponseStatusCodeSame(201);
        $this->assertJsonContains(['text' => $text]);
    }

    public function testCreateSingleTextFromNull() {
        // when
        $this->create($this->getExampleWritePayload(['text' => null]));

        // then
        $this->assertResponseStatusCodeSame(201);
        $this->assertJsonContains(['text' => null]);
    }

    public function testCreateSingleTextCleansHTMLFromText() {
        // given
        $text = ' testText<script>alert(1)</script>';

        // when
        $this->create($this->getExampleWritePayload(['text' => $text]));

        // then
        $this->assertResponseStatusCodeSame(201);
        $this->assertJsonContains([
            'text' => ' testText',
        ]);
    }
}
