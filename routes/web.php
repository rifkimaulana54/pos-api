<?php

/** @var \Laravel\Lumen\Routing\Router $router */

$router->get('/', function () use ($router) {
    return $router->app->version();
});

$router->group(['prefix' => 'order/v1_0'], function () use ($router) {
    // Matches "/api/order
    $router->get('order/{id}', 'Order\OrderController@show');
    $router->post('order/store', 'Order\OrderController@store');
    $router->put('order/{id}', 'Order\OrderController@update');
    $router->delete('order/{id}', 'Order\OrderController@destroy');
    $router->post('order', 'Order\OrderController@index');
});

$router->group(['prefix' => 'product/v1_0'], function () use ($router) {
    // Matches "/api/product
    $router->get('product/{id}', 'Product\ProductController@show');
    $router->post('product/store', 'Product\ProductController@store');
    $router->put('product/{id}', 'Product\ProductController@update');
    $router->delete('product/{id}', 'Product\ProductController@destroy');
    $router->post('product', 'Product\ProductController@index');

    // Matches "/api/category
    $router->get('category/{id}', 'Product\CategoryController@show');
    $router->post('category/store', 'Product\CategoryController@store');
    $router->put('category/{id}', 'Product\CategoryController@update');
    $router->delete('category/{id}', 'Product\CategoryController@destroy');
    $router->post('category', 'Product\CategoryController@index');
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

    // Matches "/api/acl/store
    $router->post('our-store/store', 'User\Store\StoreController@store');
    $router->get('our-store/{id}', 'User\Store\StoreController@show');
    $router->put('our-store/{id}', 'User\Store\StoreController@update');
    $router->delete('our-store/{id}', 'User\Store\StoreController@destroy');
    $router->post('our-store', 'User\Store\StoreController@index');

    // Matches "/api/acl/company
    $router->post('company/store', 'User\Company\CompanyController@store');
    $router->get('company/{id}', 'User\Company\CompanyController@show');
    $router->put('company/{id}', 'User\Company\CompanyController@update');
    $router->delete('company/{id}', 'User\Company\CompanyController@destroy');
    $router->post('company', 'User\Company\CompanyController@index');
});
