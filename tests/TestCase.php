<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Laravel\Passport\Client;
use Laravel\Passport\ClientRepository;

abstract class TestCase extends BaseTestCase
{
    protected ?Client $passwordGrantClient = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->afterApplicationCreated(function () {
            if (method_exists($this, 'refreshDatabase') || in_array(\Illuminate\Foundation\Testing\RefreshDatabase::class, class_uses_recursive($this))) {
                $clientRepository = app(ClientRepository::class);
                $clientRepository->createPersonalAccessGrantClient('Test Personal Access Client');
                $this->passwordGrantClient = $clientRepository->createPasswordGrantClient('Test Password Grant Client', confidential: true);
            }
        });
    }
}
