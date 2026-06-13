<?php

use App\Models\ThemePack;
use App\Models\World;

function createCertificateWorld(): World
{
    $theme = ThemePack::create(['name' => 'Cert Theme', 'identifier' => 'theme_cert_'.uniqid(), 'config' => []]);

    return World::create(['name' => 'Cert World', 'slug' => 'cert-world', 'theme_pack_id' => $theme->id]);
}

it('redirects guests to login with an explanation when visiting the certificate url', function () {
    $world = createCertificateWorld();

    $this->get(route('student.world.certificate', $world))
        ->assertRedirect(route('login'))
        ->assertSessionHas('auth_message', 'Please sign in to access that page.')
        ->assertSessionHas('url.intended', route('student.world.certificate', $world));
});

it('returns a 401 json response for unauthenticated json requests', function () {
    $world = createCertificateWorld();

    $this->getJson(route('student.world.certificate', $world))
        ->assertStatus(401)
        ->assertJson(['message' => 'Authentication required.']);
});
