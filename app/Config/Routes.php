<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Home::index');

// หน้า login ของ CI4: รับ username/password แล้วส่งต่อไปที่ UserAlfresco-api /auth/login
$routes->get('login', 'AuthController::loginForm');
$routes->post('login', 'AuthController::login');
$routes->get('logout', 'AuthController::logout');

// หน้าเอกสารหลัก: แสดง folder ตามสิทธิ์, ค้นหาเอกสาร, เปิดไฟล์
$routes->get('documents', 'DocumentController::index');

// Proxy API ของ CI4: หน้าเว็บเรียกเส้นนี้ แล้ว CI4 แนบ Bearer token ไปหา UserAlfresco-api ให้
$routes->get('api/folders', 'DocumentController::folders');
$routes->get('api/documents', 'DocumentController::documents');
$routes->get('api/documents/(:segment)/content', 'DocumentController::content/$1');
