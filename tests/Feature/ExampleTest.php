<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * Test that the application root redirects to Filament login.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->get('/');

        // Filament redirects unauthenticated users to login page
        $response->assertRedirect();
    }
}
