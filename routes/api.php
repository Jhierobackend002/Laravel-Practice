<?php
use App\Http\Controllers\FormSubmissionController;
use Illuminate\Support\Facades\Route;

   
Route::post('/submissions',[FormSubmissionController::class,'submit']);




