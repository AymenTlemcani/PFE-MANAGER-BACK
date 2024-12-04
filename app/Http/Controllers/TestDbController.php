<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;

class TestDbController extends Controller
{
    public function testConnection()
    {
        try {
            DB::connection()->getPdo();
            $dbName = DB::connection()->getDatabaseName();
            
            return view('db-test', [
                'status' => 'Connected',
                'message' => "Successfully connected to database: $dbName",
                'error' => null
            ]);
            
        } catch (\Exception $e) {
            return view('db-test', [
                'status' => 'Failed',
                'message' => "Could not connect to database",
                'error' => $e->getMessage()
            ]);
        }
    }
}