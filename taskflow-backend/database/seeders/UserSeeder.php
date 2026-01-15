<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Admin principal
        User::updateOrCreate(
            ['email' => 'admin@taskflow.com'],
            [
                'name' => 'Admin TaskFlow',
                'password' => Hash::make('password123'),
                'role' => 'admin',
                'email_verified_at' => now(),
            ]
        );

        // Admin alternativo
        User::updateOrCreate(
            ['email' => 'admin@taskflow.cl'],
            [
                'name' => 'Admin TaskFlow',
                'password' => Hash::make('admin123'),
                'role' => 'admin',
                'email_verified_at' => now(),
            ]
        );

        // Project Manager
        User::updateOrCreate(
            ['email' => 'juan.perez@taskflow.com'],
            [
                'name' => 'Juan Pérez',
                'password' => Hash::make('password123'),
                'role' => 'user',
                'email_verified_at' => now(),
            ]
        );

        // Técnico 1
        User::updateOrCreate(
            ['email' => 'maria.gonzalez@taskflow.com'],
            [
                'name' => 'María González',
                'password' => Hash::make('password123'),
                'email_verified_at' => now(),
            ]
        );

        // Técnico 2
        User::updateOrCreate(
            ['email' => 'carlos.rodriguez@taskflow.com'],
            [
                'name' => 'Carlos Rodríguez',
                'password' => Hash::make('password123'),
                'email_verified_at' => now(),
            ]
        );

        // Jorge Ramirez
        User::updateOrCreate(
            ['email' => 'jramirez@tnagroup.cl'],
            [
                'name' => 'Jorge Ramirez',
                'password' => Hash::make('12345678'),
                'email_verified_at' => now(),
            ]
        );

        // Daniel Tapia
        User::updateOrCreate(
            ['email' => 'dtapia@tnagroup.cl'],
            [
                'name' => 'Daniel Tapia',
                'password' => Hash::make('12345678'),
                'email_verified_at' => now(),
            ]
        );

        // Benjamin Mayorga
        User::updateOrCreate(
            ['email' => 'bmayorga@tnagroup.cl'],
            [
                'name' => 'Benjamin Mayorga',
                'password' => Hash::make('12345678'),
                'email_verified_at' => now(),
            ]
        );

        // Ivan Mera
        User::updateOrCreate(
            ['email' => 'imera@tnagroup.cl'],
            [
                'name' => 'Ivan Mera',
                'password' => Hash::make('12345678'),
                'email_verified_at' => now(),
            ]
        );

        // Vicente Acevedo
        User::updateOrCreate(
            ['email' => 'vacevedo@tnagroup.cl'],
            [
                'name' => 'Vicente Acevedo',
                'password' => Hash::make('12345678'),
                'email_verified_at' => now(),
            ]
        );

        echo "✅ Usuarios creados/actualizados exitosamente\n";
    }
}