<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Dashboard
Route::get('/dashboard', function () {
    return view('pages.admin.dashboard');
})->name('dashboard');

// Orders
Route::get('/orders', function () {
    return view('pages.admin.orders');
})->name('orders');

// Menu
Route::get('/menu', function () {
    return view('pages.admin.menu');
})->name('menu');

// Product
Route::get('/product', function () {
    return view('pages.admin.product');
})->name('product');

// Category
Route::get('/category', function () {
    return view('pages.admin.category');
})->name('category');

// Components Group
Route::get('/form', function () {
    return view('pages.admin.components.form');
})->name('form');

Route::get('/table', function () {
    return view('pages.admin.components.table');
})->name('table');

Route::get('/card', function () {
    return view('pages.admin.components.card');
})->name('card');
