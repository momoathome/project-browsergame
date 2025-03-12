<?php

namespace Tests\Unit\Controllers;

use App\Http\Controllers\AsteroidController;
use App\Services\AsteroidExplorer;
use App\Models\Asteroid;
use App\Models\Resource;
use App\Models\Spacecraft;
use App\Models\Station;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class AsteroidControllerTest extends TestCase
{
  use RefreshDatabase;

  protected $controller;

  protected function setUp(): void
  {
    parent::setUp();
    $asteroidExplorer = $this->createMock(AsteroidExplorer::class);
    $this->controller = new AsteroidController($asteroidExplorer);
  }

  public function testSearchSingleWord()
  {
    User::factory()->create();
    Station::factory()->create();
    Asteroid::factory()->create(['name' => 'TVraZa6516-54']);

    $request = new Request(['query' => 'TVraZ']);
    $response = $this->controller->search($request);

    $this->assertCount(1, $response->getData()['searched_asteroids']);
  }

  /* public function testSearchWithRarity()
  {
    Station::factory()->create();
    Asteroid::factory()->create(['rarity' => 'common']);
    Asteroid::factory()->create(['rarity' => 'rare']);

    $request = new Request(['query' => 'rare']);
    $response = $this->controller->search($request);

    $this->assertCount(1, $response->getData()['searched_asteroids']);
  }

  public function testSearchWithResource()
  {
    $asteroid = Asteroid::factory()->create();
    $resource = Resource::factory()->create(['resource_type' => 'Carbon']);
    $asteroid->resources()->attach($resource);

    $request = new Request(['query' => 'Carbon']);
    $response = $this->controller->search($request);

    $this->assertCount(1, $response->getData()['searched_asteroids']);
  }

  public function testSearchWithResourceSynonym()
  {
    $asteroid = Asteroid::factory()->create();
    $resource = Resource::factory()->create(['resource_type' => 'Carbon']);
    $asteroid->resources()->attach($resource);

    $request = new Request(['query' => 'ca']);
    $response = $this->controller->search($request);

    $this->assertCount(1, $response->getData()['searched_asteroids']);
  }

  public function testSearchWithMultipleResources()
  {
    $asteroid1 = Asteroid::factory()->create();
    $asteroid2 = Asteroid::factory()->create();
    $carbon = Resource::factory()->create(['resource_type' => 'Carbon']);
    $titanium = Resource::factory()->create(['resource_type' => 'Titanium']);
    $asteroid1->resources()->attach([$carbon->id, $titanium->id]);
    $asteroid2->resources()->attach($carbon->id);

    $request = new Request(['query' => 'Carbon Titanium']);
    $response = $this->controller->search($request);

    $this->assertCount(2, $response->getData()['searched_asteroids']);
  }

  public function testSearchWithMultipleResourcesSynonym()
  {
    $asteroid1 = Asteroid::factory()->create();
    $asteroid2 = Asteroid::factory()->create();
    $carbon = Resource::factory()->create(['resource_type' => 'Carbon']);
    $titanium = Resource::factory()->create(['resource_type' => 'Titanium']);
    $asteroid1->resources()->attach([$carbon->id, $titanium->id]);
    $asteroid2->resources()->attach($carbon->id);

    $request = new Request(['query' => 'Ca Ti']);
    $response = $this->controller->search($request);

    $this->assertCount(2, $response->getData()['searched_asteroids']);
  }

  public function testSearchWithCombinedResources()
  {
    $asteroid1 = Asteroid::factory()->create();
    $asteroid2 = Asteroid::factory()->create();
    $carbon = Resource::factory()->create(['resource_type' => 'Carbon']);
    $titanium = Resource::factory()->create(['resource_type' => 'Titanium']);
    $asteroid1->resources()->attach([$carbon->id, $titanium->id]);
    $asteroid2->resources()->attach($carbon->id);

    $request = new Request(['query' => 'Carbon-Titanium']);
    $response = $this->controller->search($request);

    $this->assertCount(1, $response->getData()['searched_asteroids']);
  }

  public function testSearchWithCombinedResourcesSynonym()
  {
    $asteroid1 = Asteroid::factory()->create();
    $asteroid2 = Asteroid::factory()->create();
    $carbon = Resource::factory()->create(['resource_type' => 'Carbon']);
    $titanium = Resource::factory()->create(['resource_type' => 'Titanium']);
    $asteroid1->resources()->attach([$carbon->id, $titanium->id]);
    $asteroid2->resources()->attach($carbon->id);

    $request = new Request(['query' => 'Ca-Ti']);
    $response = $this->controller->search($request);

    $this->assertCount(1, $response->getData()['searched_asteroids']);
  } */

}
