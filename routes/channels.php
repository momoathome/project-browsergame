<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});
Broadcast::channel('canvas', function () {
    return Auth::check();
});
Broadcast::channel('user.update.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});
Broadcast::channel('user.combat.{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});
