<?php

/** @var \Laravel\Lumen\Routing\Router $router */

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

$router->get('/', function () use ($router) {
    return $router->app->version();
});

$router->group(['prefix' => 'asset/v1_0'], function () use ($router) {
    // Matches "/api/media
    $router->get('detail/{id}', 'Asset\MediaController@show');
    $router->post('store', 'Asset\MediaController@store');
    $router->put('update/{id}', 'Asset\MediaController@update');
    $router->delete('delete/{id}', 'Asset\MediaController@destroy');
    $router->post('lists', 'Asset\MediaController@index');
});

$router->group(['prefix' => 'user/v1_0'], function () use ($router) 
{
    // Matches "/api/login
    // $router->post('login-code', 'User\AuthController@loginCode');
    $router->post('login', 'User\AuthController@login');

    // Matches "/api/acl/roles
    $router->post('acl/roles/store', 'User\ACL\RoleController@store');
    $router->get('acl/roles/{id}', 'User\ACL\RoleController@show');
    $router->put('acl/roles/{id}', 'User\ACL\RoleController@update');
    $router->delete('acl/roles/{id}', 'User\ACL\RoleController@destroy');
    $router->post('acl/roles', 'User\ACL\RoleController@index');

    // Matches "/api/acl/permissions
    $router->post('acl/permissions/store', 'User\ACL\PermissionController@store');
    $router->get('acl/permissions/{id}', 'User\ACL\PermissionController@show');
    $router->put('acl/permissions/{id}', 'User\ACL\PermissionController@update');
    $router->delete('acl/permissions/{id}', 'User\ACL\PermissionController@destroy');
    $router->post('acl/permissions/groups', 'User\ACL\PermissionController@groups');
    $router->post('acl/permissions', 'User\ACL\PermissionController@index');
    
    // Matches /api/log-histories
    $router->post('log-histories/filter', 'LogHistoryController@filter');
    $router->post('log-histories', 'LogHistoryController@index');

    // Matches "/api/register
    $router->post('register', 'User\AuthController@register');

    // Matches "/api/profile
    $router->get('profile', 'User\UserController@profile');

    // Matches "/api/users/1 
    // $router->post('import', 'User\UserController@import');
    $router->get('detail/{id}', 'User\UserController@singleUser');
    $router->put('update/{id}', 'User\UserController@updateUser');
    $router->delete('delete/{id}', 'User\UserController@deleteUser');

    // Matches /api/ update password
    $router->post('password/reset', 'User\AuthController@updatePassword');

    // Matches /api/forgot password
    $router->post('password/forgot', 'User\AuthController@forgotPassword');

    // Matches "/api/users
    $router->post('lists', 'User\UserController@allUsers');
});
