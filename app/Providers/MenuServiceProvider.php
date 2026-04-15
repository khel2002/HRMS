<?php

namespace App\Providers;

use Illuminate\Support\Facades\View;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Auth;


use Illuminate\Support\ServiceProvider;

class MenuServiceProvider extends ServiceProvider
{
  /**
   * Register services.
   */
  public function register(): void
  {
    //
  }

  /**
   * Bootstrap services.
   */
  public function boot(): void
  {
    View::composer('*', function ($view) {

      if (! Auth::check()) {
        $view->with('menuData', [json_decode('{"menu":[]}')]);
        return;
      }


      $roleName = Auth::user()->role?->name;

      $menuFile = match ($roleName) {
        'Admin'  => 'AdminMenu.json',
        'HR'     => 'HrMenu.json',
        'Employee'  => 'EmployeesMenu.json',  // any other role → employee self-service menu
      };

      $path = base_path("resources/menu/{$menuFile}");

      // Guard against missing file so the app doesn't crash
      if (! file_exists($path)) {
        $view->with('menuData', [json_decode('{"menu":[]}')]);
        return;
      }

      $menuData = json_decode(file_get_contents($path));
      $view->with('menuData', [$menuData]);
    });
  }
}
