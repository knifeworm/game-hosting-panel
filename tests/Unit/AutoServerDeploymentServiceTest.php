<?php

namespace Tests\Unit;

use App\Game;
use App\Jobs\AsyncServerDeployment;
use App\Node;
use App\Server;
use App\Services\ServerService;
use App\Services\User\AutoServerDeploymentService;
use App\Services\User\DeployCreationService;
use App\Services\User\ServerDeploymentService;
use App\Services\User\UserPanelRegistrationService;
use App\User;
use HCGCloud\Pterodactyl\Pterodactyl;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class AutoServerDeploymentServiceTest extends TestCase
{
    use RefreshDatabase;
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();

        $mocked = Mockery::mock(DeployCreationService::class);
        $this->instance(DeployCreationService::class, $mocked);
        $mocked->shouldReceive('preChecks')->once();
    }

    protected function mockServerService(bool $isInstalled): void
    {
        $mocked = Mockery::mock(ServerService::class);
        $mocked->shouldReceive('isInstalled')->andReturn($isInstalled)->once();
        $this->instance(ServerService::class, $mocked);
    }

    protected function mockServerDeploymentService(): void
    {
        $mocked = Mockery::mock(ServerDeploymentService::class);
        $mocked->shouldReceive('handle')->once();
        $this->instance(ServerDeploymentService::class, $mocked);
    }

    protected function createServer()
    {
        $game = factory(Game::class)->create();
        $node = factory(Node::class)->create();
        $user = factory(User::class)->create();

        return factory(Server::class)->create([
            'game_id' => $game->id,
            'node_id' => $node->id,
            'user_id' => $user->id,
        ]);
    }

    public function test_server_deployment_will_be_called_directly_if_server_is_installed(): void
    {
        $this->mockServerService(true);
        $this->mockServerDeploymentService();

        $server = $this->createServer();

        app(AutoServerDeploymentService::class)->handle($server, 'daily', []);
    }

    public function test_server_deployment_will_dispatch_async_server_deployment(): void
    {
        $this->expectsJobs(AsyncServerDeployment::class);
        $this->mockServerService(false);

        $server = $this->createServer();


        app(AutoServerDeploymentService::class)->handle($server, 'daily', []);
    }
}