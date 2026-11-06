<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/AttributeEventDispatcher.php';

use Practice\AttributeEventDispatcher;
use Practice\HandlesEvent;

final class Php005OrderHandlers
{
    public int $calls = 0;

    #[HandlesEvent('order.paid')]
    public function paid(array $payload): string
    {
        $this->calls++;
        return 'paid:' . $payload['id'];
    }

    #[HandlesEvent('order.cancelled')]
    public function cancelled(array $payload): string
    {
        $this->calls++;
        return 'cancelled:' . $payload['id'];
    }

    public function notAnnotated(array $payload): string
    {
        return 'ignored';
    }
}

final class Php005OtherHandlers
{
    #[HandlesEvent('inventory.reserved')]
    public function reserved(array $payload): string
    {
        return 'reserved:' . $payload['sku'];
    }
}

final class Php005DuplicateHandler
{
    #[HandlesEvent('order.paid')]
    public function duplicate(array $payload): string
    {
        return 'duplicate';
    }
}

$dispatcher = new AttributeEventDispatcher();
$orders = new Php005OrderHandlers();
$dispatcher->register($orders);

expectSame(1, $dispatcher->scannedClassCount(), 'PHP-005 scanned one handler class');
expectSame('paid:42', $dispatcher->dispatch('order.paid', ['id' => 42]), 'PHP-005 dispatch paid event');
expectSame('cancelled:7', $dispatcher->dispatch('order.cancelled', ['id' => 7]), 'PHP-005 dispatch second annotated method');
expectSame(2, $orders->calls, 'PHP-005 invokes original handler instance');
expectSame(1, $dispatcher->scannedClassCount(), 'PHP-005 dispatch does not rescan');

$dispatcher->register(new Php005OtherHandlers());
expectSame('reserved:SKU-1', $dispatcher->dispatch('inventory.reserved', ['sku' => 'SKU-1']), 'PHP-005 second handler class');
expectSame(2, $dispatcher->scannedClassCount(), 'PHP-005 two scanned handler classes');

$unknown = false;
try {
    $dispatcher->dispatch('notAnnotated', []);
} catch (InvalidArgumentException) {
    $unknown = true;
}
expectSame(true, $unknown, 'PHP-005 ignores unannotated methods');

$duplicate = false;
try {
    $dispatcher->register(new Php005DuplicateHandler());
} catch (LogicException) {
    $duplicate = true;
}
expectSame(true, $duplicate, 'PHP-005 rejects duplicate event registration');
expectSame('paid:99', $dispatcher->dispatch('order.paid', ['id' => 99]), 'PHP-005 collision does not override existing handler');

// A second instance of a previously scanned class does not need another
// Reflection scan, even though duplicate registration is rejected.
$duplicateInstance = false;
try {
    $dispatcher->register(new Php005OrderHandlers());
} catch (LogicException) {
    $duplicateInstance = true;
}
expectSame(true, $duplicateInstance, 'PHP-005 duplicate handler instance rejected');
expectSame(3, $dispatcher->scannedClassCount(), 'PHP-005 cache skips rescanning previously seen class');
