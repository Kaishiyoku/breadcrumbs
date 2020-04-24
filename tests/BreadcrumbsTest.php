<?php

declare(strict_types=1);

namespace Tabuna\Breadcrumbs\Tests;

use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Support\Facades\Route;
use Tabuna\Breadcrumbs\Breadcrumbs;
use Tabuna\Breadcrumbs\Trail;

class BreadcrumbsTest extends TestCase
{
    public function testBreadcrumbsDefined(): void
    {
        Route::get('/breadcrumbs-home', function () {
            return Breadcrumbs::current()->toJson();
        })->name('breadcrumbs-home');

        Breadcrumbs::for('breadcrumbs-home', function (Trail $trail) {
            return $trail->push('Home', 'http://localhost/');
        });

        $this->get('/breadcrumbs-home')
            ->assertJson([
                [
                    'title' => 'Home',
                    'url'   => 'http://localhost/',
                ],
            ]);
    }

    public function testBreadcrumbsUndefined(): void
    {
        Route::get('/undefined', function () {
            return Breadcrumbs::current()->toJson();
        })->name('breadcrumbs-home');

        $this->get('/undefined')->assertOk();
    }

    public function testBreadcrumbsParent(): void {

        Route::get('/', function () {

        })
            ->name('home')
            ->breadcrumbs(function (Trail $trail) {
                $trail->push('Home', route('home'));
            });


        Route::get('/about', function () {
            return Breadcrumbs::current()->toJson();
        })
            ->name('about')
            ->breadcrumbs(function (Trail $trail) {
                return $trail->parent('home')->push('About', route('about'));
            });

        $this->get('/about')
            ->assertJson([
                [
                    'title' => 'Home',
                    'url'   => 'http://127.0.0.1:8000',
                ],
                [
                    'title' => 'About',
                    'url'   => 'http://127.0.0.1:8000/about',
                ]
            ]);
    }

    public function testBreadcrumbsRoute(): void
    {
        Route::get('breadcrumbs-about-test', function () {
            return Breadcrumbs::current()->toJson();
        })
            ->name('breadcrumbs.about')
            ->breadcrumbs(function (Trail $trail) {
                return $trail->push('About', \route('breadcrumbs.about'));
            });

        $this->get('breadcrumbs-about-test')
            ->assertJson([
                [
                    'title' => 'About',
                    'url'   => 'http://127.0.0.1:8000/breadcrumbs-about-test',
                ],
            ]);
    }

    public function testBreadcrumbsParameters(): void
    {
        $random = random_int(10, 100);

        Route::get('breadcrumbs-about-test/{bind}', function (UrlBind $bind) {
            $bind->getRouteKey();

            return Breadcrumbs::current()->toJson();
        })
            ->middleware(SubstituteBindings::class)
            ->name('breadcrumbs.about')
            ->breadcrumbs(function (Trail $trail, $bind) {
                return $trail->push('Sum', $bind);
            });

        $this->get(\route('breadcrumbs.about', $random))
            ->assertJson([
                [
                    'title' => 'Sum',
                    'url'   => $random + $random,
                ],
            ]);
    }

}