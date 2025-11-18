<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

Broadcast::channel('new-orders', fn($user, $id) => true);

Broadcast::channel('accepted-order.{id}', fn($user, $id) => true);

Broadcast::channel('rejected-order.{id}', fn($user, $id) => true);

Broadcast::channel('shipped-order.{id}', fn($user, $id) => true);

Broadcast::channel('completed-order.{id}', fn($user, $id) => true);

Broadcast::channel('a-driver-to-order.{id}', fn($user, $id) => true);

Broadcast::channel('r-driver-form-order.{id}', fn($user, $id) => true);

Broadcast::channel('ride-request.{user}', fn(User $user) => true /* $user->id === $rideRequest->user_id || $user->id === $rideRequest->driver_id || !$user->isPenalityInProgress */);

Broadcast::channel('ride-request-availability.{user}', fn(User $user) => !$user->isPenalityInProgress);

Broadcast::channel('ride-request-cancelled.{user}', fn(User $user) => true);

Broadcast::channel('ride-request-delivered.{user}', fn(User $user) => true);

Broadcast::channel('ride-request-picked-up.{user}', fn(User $user) => true);

Broadcast::channel('ride-request-arrived.{user}', fn(User $user) => true);

Broadcast::channel('ride-request-arrived-restaurant.{user}', fn(User $user) => true);

Broadcast::channel('ride-request-ended.{user}', fn(User $user) => true);

Broadcast::channel('restaurant-delivery.{restaurant}', fn(User $user, $restaurant) => $user->restaurant && $user->restaurant->id === (int) $restaurant);

Broadcast::channel('driver-updates.{driver}', fn(User $user, $driver) => $user->id === (int) $driver);

Broadcast::channel('client-updates.{client}', fn(User $user, $client) => $user->id === (int) $client);

Broadcast::channel(
    'private-order-restaurant.{restaurant}',
    fn(User $user, $restaurant) => $user->restaurant && $user->restaurant->id === (string) $restaurant,
);

Broadcast::channel(
    'private-order-customer.{customer}',
    fn(User $user, $customer) => $user->id === (string) $customer,
);

Broadcast::channel(
    'private-order-driver.{driver}',
    fn(User $user, $driver) => $user->id === (string) $driver && $user->role === 'driver' && !$user->isPenalityInProgress,
);

Broadcast::channel(
    'monitor',
    fn(User $user) => in_array($user->role, ['admin', 'super-admin']),
);
