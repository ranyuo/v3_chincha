<?php

namespace Tests\Feature;

use App\Exceptions\PortalErrors;
use PDOException;
use PortalError;
use Tests\TestCase;

class PortalRoutesTest extends TestCase
{
    public function test_previous_php_addresses_redirect_to_laravel_routes(): void
    {
        foreach ([
            '/mesa-de-partes.php' => '/mesa-de-partes',
            '/libro-de-reclamaciones.php' => '/libro-de-reclamaciones',
            '/bolsa-laboral.php' => '/bolsa-laboral',
            '/certificados.php' => '/certificados',
            '/install.php' => '/instalar',
            '/accounts.php' => '/cuentas',
            '/roles.php' => '/roles',
            '/auditoria.php' => '/auditoria',
            '/respaldos.php' => '/respaldos',
        ] as $from => $to) {
            $this->get($from)->assertStatus(301)->assertRedirect($to);
        }
    }

    public function test_unknown_api_route_answers_json_message(): void
    {
        $this->getJson('/api/no-existe')->assertNotFound()->assertExactJson(['message' => 'Página no encontrada.']);
    }

    public function test_unavailable_database_shows_generic_message_with_security_headers(): void
    {
        $this->get('/')
            ->assertStatus(503)
            ->assertSee('No se pudo completar la operación')
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('Cache-Control', 'no-store, private');

        $this->getJson('/api/portal')->assertStatus(503)->assertJsonStructure(['message']);
    }

    public function test_admin_pages_require_a_session(): void
    {
        foreach (['/cuentas', '/roles', '/auditoria', '/respaldos'] as $page) {
            $this->get($page)->assertForbidden()->assertSee('Inicia sesión para continuar.');
        }
    }

    public function test_portal_errors_keep_their_status_and_message(): void
    {
        $this->assertSame([409, 'Conflicto'], PortalErrors::describe(new PortalError(409, 'Conflicto')));
        $this->assertSame([503, PortalErrors::NOT_INSTALLED], PortalErrors::describe(self::missingTable()));
    }

    private static function missingTable(): PDOException
    {
        $error = new PDOException('Base table or view not found');
        (new \ReflectionProperty(\Exception::class, 'code'))->setValue($error, '42S02');

        return $error;
    }
}
