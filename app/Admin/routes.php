<?php

use Illuminate\Routing\Router;

Admin::registerHelpersRoutes();

Route::group([
    'prefix'        => config('admin.prefix'),
    'namespace'     => Admin::controllerNamespace(),
    'middleware'    => ['web', 'admin'],
], function (Router $router) {

    $router->get('/', 'HomeController@index');
    $router->get('/sign', 'SignLogscontroller@index');


    $router->resources([
        'vcats'  => 'VcatsController',
        'vusers'  => 'VusersController',
        'conferences'  => 'ConferencesController',
        'salesmen' => 'SalesmenController',
        'managers' => 'ManagersController',
        'hotels' => 'HotelsController',
        'provinces' => 'ProvincesController',
    ]);

    $router->post('vusers/enter', 'VusersController@enter');
    $router->patch('del_card', 'VusersController@delCard');
    $router->post('vusers/send_sms', 'VusersController@sendSms');

    $router->get('/enter', 'Entercontroller@index');
});
