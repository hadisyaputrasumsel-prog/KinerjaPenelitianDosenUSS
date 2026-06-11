<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\DashboardController;

Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
Route::get('/lecturers', [DashboardController::class, 'lecturers'])->name('lecturers');
Route::get('/crawl', [DashboardController::class, 'crawl'])->name('crawl');
Route::get('/analytics', [DashboardController::class, 'analytics'])->name('analytics');
Route::get('/sinta-proxy', [DashboardController::class, 'sintaProxy'])->name('sinta.proxy');
Route::get('/accreditation', [DashboardController::class, 'accreditation'])->name('accreditation');
Route::get('/crawl-scholar', [DashboardController::class, 'crawlScholar'])->name('crawl.scholar');
Route::post('/crawl-sinta', [DashboardController::class, 'crawlSinta'])->name('crawl.sinta');
Route::post('/update-lecturer', [DashboardController::class, 'updateLecturer'])->name('lecturer.update');
Route::post('/sync-lecturer-sinta', [DashboardController::class, 'syncLecturerSinta'])->name('lecturer.sync.sinta');
Route::post('/sync-publications', [DashboardController::class, 'syncPublications'])->name('lecturer.sync.publications');
Route::get('/lecturer/{id}/publications', [DashboardController::class, 'getPublications'])->name('lecturer.publications');
