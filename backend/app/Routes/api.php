<?php
// backend/app/Routes/api.php

// Public Routes (No CSRF required for auth endpoints)
Route::get('csrf-token', 'AuthController@csrf');
Route::post('register', 'AuthController@register');
Route::post('login', 'AuthController@login');
Route::post('refresh-token', 'AuthController@refresh');
Route::post('csrf-regenerate', 'AuthController@regenerateCsrf', ['AuthMiddleware']);

//notification
Route::get('notifications', 'NotificationController@index', ['AuthMiddleware']);
Route::get('notifications/unread-count', 'NotificationController@unreadCount', ['AuthMiddleware']);
Route::post('notifications/read/{id}', 'NotificationController@markRead', ['AuthMiddleware', 'CsrfMiddleware']);
Route::post('notifications/read-all', 'NotificationController@markAllRead', ['AuthMiddleware', 'CsrfMiddleware']);


// Protected Routes
// Middleware: AuthMiddleware

// Doctors
Route::get('doctors', 'DoctorController@index', ['AuthMiddleware', 'RoleMiddleware:admin,provider,doctor,nurse,patient,receptionist']);
Route::get('doctors/{id}', 'DoctorController@show', ['AuthMiddleware', 'RoleMiddleware:admin,provider,doctor,nurse,patient,receptionist']);
Route::post('doctors', 'DoctorController@store', ['AuthMiddleware', 'CsrfMiddleware', 'RoleMiddleware:admin']);
Route::put('doctors/{id}', 'DoctorController@update', ['AuthMiddleware', 'CsrfMiddleware', 'RoleMiddleware:admin']);
Route::delete('doctors/{id}', 'DoctorController@delete', ['AuthMiddleware', 'CsrfMiddleware', 'RoleMiddleware:admin']);


// User & Role Management
Route::get('users', 'UserController@index', ['AuthMiddleware', 'RoleMiddleware:admin']);
Route::get('users/{id}', 'UserController@show', ['AuthMiddleware', 'RoleMiddleware:admin']);
Route::post('users', 'UserController@store', ['AuthMiddleware', 'CsrfMiddleware', 'RoleMiddleware:admin']);
Route::put('users/{id}', 'UserController@update', ['AuthMiddleware', 'CsrfMiddleware', 'RoleMiddleware:admin']);
Route::delete('users/{id}', 'UserController@delete', ['AuthMiddleware', 'CsrfMiddleware', 'RoleMiddleware:admin']);

// Profile
Route::get('profile', 'UserController@getProfile', ['AuthMiddleware']);
Route::put('profile', 'UserController@updateProfile', ['AuthMiddleware', 'CsrfMiddleware']);

// Patient Management
Route::get('patients', 'PatientController@index', ['AuthMiddleware', 'RoleMiddleware:admin,provider,doctor,nurse,patient,receptionist']);
Route::get('patients/{id}', 'PatientController@show', ['AuthMiddleware', 'RoleMiddleware:admin,provider,doctor,nurse,patient,receptionist']);
Route::post('patients', 'PatientController@store', ['AuthMiddleware', 'CsrfMiddleware', 'RoleMiddleware:admin,provider,doctor,nurse,receptionist']);
Route::put('patients/{id}', 'PatientController@update', ['AuthMiddleware', 'CsrfMiddleware', 'RoleMiddleware:admin,provider,doctor,nurse,receptionist']);
Route::delete('patients/{id}', 'PatientController@delete', ['AuthMiddleware', 'CsrfMiddleware', 'RoleMiddleware:admin']);
Route::get('patients/{id}/appointments', 'PatientController@appointments', ['AuthMiddleware', 'RoleMiddleware:admin,provider,doctor,nurse,patient,receptionist']);

// Appointment Management
Route::get('appointments', 'AppointmentController@index', ['AuthMiddleware', 'RoleMiddleware:admin,provider,doctor,nurse,patient,receptionist']);
Route::get('appointments/upcoming', 'AppointmentController@upcoming', ['AuthMiddleware', 'RoleMiddleware:admin,provider,doctor,nurse,patient,receptionist']);
Route::get('appointments/calendar', 'AppointmentController@calendar', ['AuthMiddleware', 'RoleMiddleware:admin,provider,doctor,nurse,receptionist,patient']);
Route::get('appointments/{id}', 'AppointmentController@show', ['AuthMiddleware', 'RoleMiddleware:admin,provider,doctor,nurse,patient,receptionist']);
Route::get('appointments/{id}/tooltip', 'AppointmentController@tooltip', ['AuthMiddleware', 'RoleMiddleware:admin,provider,doctor,nurse,receptionist']);
Route::post('appointments', 'AppointmentController@store', ['AuthMiddleware', 'CsrfMiddleware', 'RoleMiddleware:admin,provider,doctor,patient,receptionist']);
Route::put('appointments/{id}', 'AppointmentController@update', ['AuthMiddleware', 'CsrfMiddleware', 'RoleMiddleware:admin,provider,doctor,nurse,patient,receptionist']);
Route::delete('appointments/{id}', 'AppointmentController@delete', ['AuthMiddleware', 'CsrfMiddleware', 'RoleMiddleware:admin,provider,doctor,nurse,patient,receptionist']);

// Prescription
Route::get('prescriptions', 'PrescriptionController@index', ['AuthMiddleware', 'RoleMiddleware:admin,provider,doctor,nurse,pharmacist']);
Route::get('prescriptions/{id}', 'PrescriptionController@show', ['AuthMiddleware', 'RoleMiddleware:admin,provider,doctor,nurse,pharmacist']);
Route::post('prescriptions', 'PrescriptionController@store', ['AuthMiddleware', 'CsrfMiddleware', 'RoleMiddleware:admin,provider,doctor']);
Route::patch('prescriptions/{id}/status', 'PrescriptionController@updateStatus', ['AuthMiddleware', 'CsrfMiddleware', 'RoleMiddleware:admin,pharmacist']);
Route::get('prescriptions/status/{status}', 'PrescriptionController@getByStatus', ['AuthMiddleware', 'RoleMiddleware:admin,pharmacist']);
Route::delete('prescriptions/{id}', 'PrescriptionController@delete', ['AuthMiddleware', 'CsrfMiddleware', 'RoleMiddleware:admin,provider,doctor']);
Route::get('prescriptions/appointment/{id}','PrescriptionController@getByAppointment',[ 'AuthMiddleware','RoleMiddleware:nurse,doctor']);
// Dashboard
Route::get('dashboard', 'DashboardController@index', ['AuthMiddleware', 'RoleMiddleware:admin,provider,doctor']);
Route::get('dashboard/analytics', 'DashboardController@getAnalytics', ['AuthMiddleware', 'RoleMiddleware:admin']);
// Communication
Route::post('communication/notes', 'CommunicationController@storeNote', ['AuthMiddleware', 'CsrfMiddleware', 'RoleMiddleware:provider,doctor,nurse']);
Route::get('communication/notes/appointment/{id}', 'CommunicationController@getNotesByAppointment', ['AuthMiddleware', 'RoleMiddleware:provider,doctor,nurse']);
Route::get('communication/history', 'CommunicationController@getHistory', ['AuthMiddleware', 'RoleMiddleware:provider,doctor,nurse']);


// Billing
Route::get('billing', 'BillingController@index', ['AuthMiddleware', 'RoleMiddleware:admin,provider,doctor,pharmacist']);
Route::get('billing/summary', 'BillingController@summary', ['AuthMiddleware', 'RoleMiddleware:admin,provider,doctor']);
Route::get('billing/{id}', 'BillingController@show', ['AuthMiddleware', 'RoleMiddleware:admin,provider,doctor']);
Route::post('billing', 'BillingController@store', ['AuthMiddleware', 'CsrfMiddleware', 'RoleMiddleware:admin,provider,doctor,pharmacist']);
Route::patch('billing/{id}/status', 'BillingController@updateStatus', ['AuthMiddleware', 'CsrfMiddleware', 'RoleMiddleware:admin,provider,doctor']);
Route::get('patients/{id}/billing', 'BillingController@patientInvoices', ['AuthMiddleware', 'RoleMiddleware:admin,provider,doctor,patient']);

// Staff
Route::get('staff', 'StaffController@index', ['AuthMiddleware', 'RoleMiddleware:admin,doctor,nurse']);
Route::get('staff/{id}', 'StaffController@show', ['AuthMiddleware', 'RoleMiddleware:admin,doctor,nurse']);
Route::post('staff', 'StaffController@store', ['AuthMiddleware', 'CsrfMiddleware', 'RoleMiddleware:admin']);
Route::put('staff/{id}', 'StaffController@update', ['AuthMiddleware', 'CsrfMiddleware', 'RoleMiddleware:admin']);
Route::delete('staff/{id}', 'StaffController@delete', ['AuthMiddleware', 'CsrfMiddleware', 'RoleMiddleware:admin']);


// Settings
Route::post('logout', 'AuthController@logout', ['CsrfMiddleware']);
Route::post('change-password', 'AuthController@changePassword', ['AuthMiddleware', 'CsrfMiddleware']);
Route::post('log', 'AuthController@logRemote', []); 

// Inventory Routes
Route::get('medicines', 'InventoryController@index', ['AuthMiddleware', 'RoleMiddleware:admin,pharmacist']);
Route::get('medicines/{id}', 'InventoryController@show', ['AuthMiddleware', 'RoleMiddleware:admin,pharmacist']);
Route::post('medicines', 'InventoryController@store', ['AuthMiddleware', 'CsrfMiddleware', 'RoleMiddleware:admin,pharmacist']);
Route::put('medicines/{id}', 'InventoryController@update', ['AuthMiddleware', 'CsrfMiddleware', 'RoleMiddleware:admin,pharmacist']);
Route::delete('medicines/{id}', 'InventoryController@delete', ['AuthMiddleware', 'CsrfMiddleware', 'RoleMiddleware:admin,pharmacist']);

