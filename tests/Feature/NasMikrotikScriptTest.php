<?php

namespace Tests\Feature;

use App\Models\Radius\Nas;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NasMikrotikScriptTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'role' => 'administrator',
            'is_active' => true
        ]);
    }

    /** @test */
    public function test_nas_index_renders_with_get_script_button()
    {
        $this->actingAs($this->user);

        $router = Nas::create([
            'nasname' => '10.10.10.1',
            'shortname' => 'RouterTest',
            'secret' => 'supersecret123',
            'description' => 'Mikrotik Test router',
        ]);

        $response = $this->get(route('nas.index'));
        $response->assertStatus(200);

        // Verification of Get Script and Edit actions
        $response->assertSee('Get Script');
        $response->assertSee('Edit');
        $response->assertSee('10.10.10.1');
        $response->assertSee('RouterTest');
        $response->assertSee('openScriptModal'); // Alpine action check

        // Verify active IP label and read-only input
        $response->assertSee('Radius Server IP Address (Active)');
        $response->assertSee('readonly');
        $response->assertSee('disabled');
    }

    /** @test */
    public function test_nas_edit_renders_with_mikrotik_script_panel()
    {
        $this->actingAs($this->user);

        $router = Nas::create([
            'nasname' => '10.10.10.2',
            'shortname' => 'RouterEdit',
            'secret' => 'editsecret456',
            'description' => 'Mikrotik Edit router',
        ]);

        $response = $this->get(route('nas.edit', $router->id));
        $response->assertStatus(200);

        // Verify script variables are bound in view
        $response->assertSee('Mikrotik Script');
        $response->assertSee('editsecret456'); // injected in x-data state
        $response->assertSee('radiusIp');

        // Verify active IP label and read-only input on edit view
        $response->assertSee('Radius Server IP Address (Active)');
        $response->assertSee('readonly');
        $response->assertSee('disabled');
    }
}
