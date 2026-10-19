<?php

namespace Tests\Unit\Services;

use App\Services\PayloadService;
use InvalidArgumentException;
use Tests\TestCase;

class PayloadServiceTest extends TestCase
{
    public function test_removed_encryption_type_is_rejected_before_payload_build(): void
    {
        $this->expectException(InvalidArgumentException::class);

        app(PayloadService::class)->createPayloadProfile(
            'profile',
            'windows',
            ['encryption_type' => 'rsa'],
        );
    }

    public function test_removed_encryption_type_does_not_generate_a_decryptor_payload(): void
    {
        $victim = (object) [
            'encryption_type' => 'RSA',
            'encryption_key' => 'legacy-key',
        ];

        $decryptorPath = app(PayloadService::class)->createDecryptorPayload($victim);

        $this->assertNull($decryptorPath);
    }
}
