<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Model;

class Submission extends Model
{
    use HasFactory;

    // Define the table name if it's not the plural form of the model name
    protected $table = 'form_submissions';

    // Define the fillable columns to allow mass assignment
    protected $fillable = [
        'email',
        'phone',
        'accept',
        'submitted_at', // We can store the submission timestamp
    ];

    // Set the dates we want to use as Carbon instances (automatically handled)
    protected $dates = ['submitted_at'];

    // You can also define relationships if needed, e.g., if you have a user model
}
