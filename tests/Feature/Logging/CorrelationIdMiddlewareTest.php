<?php

namespace Tests\Feature\Logging;

use App\Http\Middleware\CorrelationId;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Tests\TestCase;

class CorrelationIdMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Route::get('/_test/correlation', function () {
            return ['correlation_id' => Context::get(CorrelationId::CONTEXT_KEY)];
        });
    }

    public function test_echoes_valid_incoming_header(): void
    {
        $uuid = Str::uuid();
        $response = $this->withHeaders(['X-Correlation-ID' => $uuid])
            ->get('/_test/correlation');

        $response->assertOk();
        $response->assertHeader('X-Correlation-ID', $uuid);
        $response->assertJson(['correlation_id' => $uuid]);
    }

    public function test_generates_id_when_header_missing(): void
    {
        $response = $this->get('/_test/correlation');

        $response->assertOk();
        $this->assertNotEmpty($response->headers->get('X-Correlation-ID'));
        $this->assertNotEmpty($response->json('correlation_id'));
    }

    public function test_generates_id_when_header_is_invalid(): void
    {
        $response = $this->withHeaders(['X-Correlation-ID' => 'bad value with spaces!'])
            ->get('/_test/correlation');

        $response->assertOk();
        $generated = $response->headers->get('X-Correlation-ID');
        $this->assertNotSame('bad value with spaces!', $generated);
        $this->assertMatchesRegularExpression('/^[A-Za-z0-9._\-:]+$/', (string) $generated);
    }

    public function test_generates_id_when_header_too_long(): void
    {
        $tooLong = str_repeat('a', 65);

        $response = $this->withHeaders(['X-Correlation-ID' => $tooLong])
            ->get('/_test/correlation');

        $response->assertOk();
        $this->assertNotSame($tooLong, $response->headers->get('X-Correlation-ID'));
    }
}
