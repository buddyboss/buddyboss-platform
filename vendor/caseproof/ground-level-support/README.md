Ground Level Support
====================

The Ground Level Support package is a set of abstracts, contracts, exceptions, models, traits
and utilities for the [Ground Level Framework](https://github.com/caseproof/ground-level-php).

---

## Installation

```bash
composer config repositories.ground-level-support vcs https://github.com/caseproof/ground-level-support
composer require caseproof/ground-level-support
```


## Concerns

### [\GroundLevel\Support\Concerns\HasEvents](https://github.com/caseproof/ground-level-php/blob/main/src/GroundLevel/Support/Concerns/HasEvents.php)

A trait that implements the [\GroundLevel\Support\Contracts\Eventable](https://github.com/caseproof/ground-level-php/blob/main/src/GroundLevel/Support/Contacts/Eventable.php) contract.

This trait has methods for enabling event emitting and subscription within a class.

### Usage

#### Emitting an Event

The following example shows the basic usage of the trait in a class in order to faciliate emitting events. 

```php

use \GroundLevel\Support\Concerns\HasEvents;

class OrderController
{
    use HasEvents;
    
    public const EVENT_NEW_ORDER = 'new.order';

    public function newOrder(array $orderData): Order
    {
        $order = new Order($orderData);

        /**
         * Emits an event that allows other classes to modify the order immediately
         * following instantiation.
         */
        $this->emit(self::EVENT_NEW_ORDER, $order, $orderData);

        return $order;
    }
}
```

#### Subscribing to an Event

Another class may subscribe to the event, in this case a logger:

```php
class OrderLogger
{
    public function __construct(OrderController $ctrl)
    {
        $ctrl->on(
            OrderController::EVENT_NEW_ORDER,
            function(Order $order, array $orderData): void
            {
                $this->log($order->toArray(), $orderData);
            }
        )
    }

    public function log(...$data): void
    {
        // Logs $data.
    }
}
```

#### Modifying data from a listener

If you'd like to create an event which allows a listener to modify data you can do so by passing a closure that modifies
the data being passed to the listener. In this example an event is emitted prior to the instantiation of an order
allowing the listener to modify the `$orderData` array:

```php

use \GroundLevel\Support\Concerns\HasEvents;

class OrderController
{
    use HasEvents;
    
    public const EVENT_NEW_ORDER_DATA = 'new.order.data';

    public function newOrder(array $orderData): Order
    {
        $this->emit(
            self::EVENT_NEW_ORDER_DATA,
            $orderData,
            static function (array $newData) use(&$orderData): void {
                $orderData = $newData;
            },
        )

        // Order data now contains the additional key, `foo`.

        // Rest of the function.
    }
}

class OrderCustomizer
{
    public function __construct(OrderController $ctrl)
    {
        $ctrl->on(
            OrderController::EVENT_NEW_ORDER_DATA,
            static function(array $orderData, \Closure $modifyData): void
            {
                $orderData['foo'] = 'bar';
                $modifyData($orderData);
            }
        )
    }
}
```

### Best Practices

In order to promote consistency across Ground Level, whenever code utilizes events, the event name should be specified
through a public class constant (as seen in the examples above). While this will not be enforced through the code itself,
an enum, or through automated coding standards, adhering to this guideline will promote consistency.

Using class constants is preferable to "magic" strings because they can be read by IDEs and if you misstype the constant
that will result in an obvious error as opposed to a string which may be harder to track down and identify.

### Documenting Events

When emitting events in Ground Level components, you should always include an event docblock immediately before the line
of code that calls the `emit()` method.

The internal console command, `ground-level docs:events` automatically locates and parses these docblocks and writes them
into the README.md of the component where the event is emitted. This ensures that events are easily discoverable by developers
without having to search the codebase to find them.

#### Example Event Docblock

- The comment should open with a description (this may span multiple lines)
- Utilize the custom `@event` tag which should be followed by the fully-qualified class/constant name
- Each argument passed to the `emit()` method should be documented using php docblock `@param` tags: type, argument name, description.

```php
/**
 * An event that does something
 * 
 * @event \GroundLevel\ComponentName\Classname::EVENT_DOES_SOMETHING
 * 
 * @param int   $arg1 The ID of the thing that's emitting an event.
 * @param array $arg2 The data being emitted by the event.
 */
$this->emit(
    static::EVENT_DOES_SOMETHING,
    $arg1,
    $arg2
);
```
