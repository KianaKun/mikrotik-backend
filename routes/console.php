<?php

use Illuminate\Support\Facades\Schedule;

// Generate token setiap hari jam 07:00 WIB
Schedule::command('tokens:generate')->dailyAt('07:00');