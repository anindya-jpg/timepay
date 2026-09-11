<?php

use App\Models\TeamInvitation;
use Illuminate\Support\Facades\Schedule;

Schedule::command('absensi:tandai-alpha')
    ->dailyAt('13:00')
    ->timezone('Asia/Jakarta');

Schedule::command('absensi:hapus-foto-kedaluwarsa')
    ->hourly()
    ->timezone('Asia/Jakarta');

Schedule::call(function () {
    TeamInvitation::query()
        ->whereNotNull('expires_at')
        ->where('expires_at', '<', now())
        ->delete();
})->daily()->description('Delete expired team invitations');