# Types Collections

[![Latest Version on Packagist][ico-version]][link-packagist]
[![Total Downloads][ico-downloads]][link-downloads]
[![Software License][ico-license]](LICENSE)

This package provides **full-featured collections** for primitive types, and generic classes to build your own strongly-typed collections. Each collection has chainable methods to perform traversal, filter and projection operations.


Example:
```php
IntCollection::fromArray([0, 1, 2, 3, 4, 5, 6, 7, 8, 9])
    ->where(fn($n) => $n > 4)
    ->append(10)
    ->select(static fn($n) => $n * 10)
    ->forEach(static fn(int $n) => var_dump($n));

// Outputs:
//   int(50)
//   int(60)
//   int(70)
//   int(80)
//   int(90)
//   int(100)
```




Chapters:
1. Available collections
    - [Primitive-type collections](#primitive-collections)
    - [Class collections](#class-collections)
2. Basic usage
   - [Constructors](#usage-constructors)
   - [Modifying the collection](#usage-modifying)



## Installation
This package requires **PHP 7.4+**

Add it as Composer dependency:
```sh
$ composer require mediagone/types-collections
```


## 1. Available collections

### <a name="primitive-collections"></a>Primitive-type collections

The `Mediagone\Types\Collections\Types` namespace provides strongly-typed collections for all PHP's primitive types:
- `ArrayCollection`: _a strongly-typed collection that can only contain PHP **array** values._
- `BoolCollection`: _a strongly-typed collection that can only contain PHP **boolean** values._
- `CallableCollection`: _a strongly-typed collection that can only contain PHP **callable** values._
- `FloatCollection`: _a strongly-typed collection that can only contain PHP **float** values._
- `IntCollection`: _a strongly-typed collection that can only contain PHP **integer** values._
- `MixedCollection`: _a strongly-typed collection that can only contain PHP **mixed** values._
- `ObjectCollection`: _a strongly-typed collection that can only contain PHP **object** values._
- `ResourceCollection`: _a strongly-typed collection that can only contain PHP **resource** values._
- `StringCollection`: _a strongly-typed collection that can only contain PHP **string** values._


### <a name="class-collections"></a>Class collections

The library also provides an abstract class to build strongly-typed class collections easily.

Start by creating a class that extends `Mediagone\Types\Collections\Types\ClassCollection` and implements the `classFqcn` method:
```php
use App\Foo;
use Mediagone\Types\Collections\ClassCollection;

class FooCollection extends ClassCollection
{
    protected static function classFqcn() : string
    {
        return Foo::class;
    }
}
```
 If you're using a static analyser tool, you must specify the type for the generic base collection with this simple annotation:
```php
/*
 * @extends ClassCollection<Foo>
 */
class FooCollection extends ClassCollection
{
```

And... that's all! Your custom collection now only accepts Foo instances.


## 2. Basic usage

### <a name="usage-constructors"></a>Constructors

#### a. Empty
You can create an empty collection using the `new` static factory method:
```php
$collection = StringCollection::new();
```

#### b. From array
You can also instantiate any collection with initial data using `fromArray` and items can be retrieved as a PHP array using `toArray` method, for example:
```php
$collection = StringCollection::fromArray(['item1', 'item2', '3']);
var_dump($collection->toArray());
// Outputs:
//   array(3) {
//     [0] => string(5) "item1"
//     [1] => string(5) "item2"
//     [2] => string(1) "3"
//   }
```

Typed collections throw an error if invalid items are added to the collection:
```php
// Throws a TypeError exception because this collection accepts only integer instances
$collection = IntCollection::fromArray([1, 2, 'invalid item']);
```

#### c. From repeated value
Collection can also be created with initial repeated value `fromRepeatedValue`:
```php
$collection = StringCollection::fromRepeatedValue('something', 3);
var_dump($collection->toArray());
// Outputs:
//   array(3) {
//     [0] => string(9) "something"
//     [1] => string(9) "something"
//     [2] => string(9) "something"
//   }
```

#### d. Additional constructors

Some collections implement specific static factory methods related to the underlying type (take a look to each class to discover specific available factories), for example in the `IntCollection` class :
```php
$collection = IntCollection::fromRange(2, 5);
var_dump($collection->toArray());
// Outputs:
//   array(5) {
//     [0] => int(2)
//     [1] => int(3)
//     [2] => int(4)
//     [3] => int(5)
//   }
```


### <a name="usage-modifying"></a>Modifying the collection
New elements can be added after the collection's initialization using `append` or `prepend` methods:
```php
$collection = StringCollection::fromArray(['item1', 'item2']);

// Add a value at the end of the collection
$collection->append('item3');

// Add a value at the start of the collection
$collection->prepend('item0');

var_dump($collection->toArray());
// Outputs:
//   array(4) {
//     [0] => string(5) "item0"
//     [1] => string(5) "item1"
//     [2] => string(5) "item2"
//     [3] => string(5) "item3"
//   }
```
>_Note: item indexes always start at 0, even after inserting, removing or reordering items._

But, the base collection class offers a lot more useful methods:

- _**Conversion** methods:_
  - `toArray`: Return the collection's items as an array.
  - `toCollection`: Converts the collection into a new collection type, all items must be valid in the target collection.

- _**Element** methods:_
  - `contains`: Determines whether the collection contains a specified item.
  - `append`: Adds an item to the end of the collection.
  - `prepend`: Adds an item to the beginning of the collection.
  - `concat`: Merges a collection into the current collection's items.
  - `remove`: Removes an item from the collection.
  - `first`: Returns the first item of the collection.
  - `firstOrDefault`: Returns the first item of the collection or a default value if no such item is found.
  - `last`: Returns the last item of the collection.
  - `lastOrDefault`: Returns the last item of the collection or the specified default value if no such item is found.
  - `single`: Returns the only item of the collection or throws an exception if more than one item exists.
  - `singleOrDefault`: Returns the only item of the collection or throws an exception if more than one item exists.
  - `random`: Returns the only item of the collection or throws an exception if more than one item exists.

- _**Partitioning** methods:_
  - `skip`: Bypasses a specified number of items in the collection and then returns the remaining items.
  - `skipLast`: Returns a new collection that contains the items from source with the last count items of the source collection omitted.
  - `skipWhile`: Bypasses items in the collection as long as a specified condition is true and then returns the remaining items.
  - `take`: Returns a specified number of contiguous items from the start of the collection.
  - `takeLast`: Returns a new collection that contains the last count items from source.
  - `takeWhile`: Returns items from the collection as long as a specified condition is true.
  - `distinct`: Removes duplicated items from the collection.
  - `distinctBy`: Removes duplicated items from the collection according to a specified key selector function.
  - `where`: Filters the collection items based on a predicate.
  - `except`: Computes the difference of collections.
  - `exceptBy`: Computes the set difference of two sequences according to a specified key selector function.
  - `intersect`: Computes the set intersection of two collections.
  - `intersectBy`: Computes the set difference of two sequences according to a specified key selector function.

- _**Ordering** methods:_
  - `shuffle`: Randomizes the order of the items in the collection.
  - `reverse`: Inverts the order of the items in the collection.
  - `sort`: Sorts the items of the collection in ascending order according to a key.
  - `sortDescending`: Sorts the items of the collection in descending order according to a key.
  - `sortBy`: Sorts the items of the collection in ascending order according to a key.
  - `sortByDescending`: Sorts the items of the collection in descending order according to a key.

- _**Aggregation** methods:_
  - `count`: Returns the number of items in the collection.
  - `min`: Returns the minimum value of the collection.
  - `max`: Returns the maximum value of the collection.
  - `average`: Computes the average of the collection values.
  - `sum`: Computes the sum of the collection of numeric values.
  - `aggregate`: Applies an accumulator function over a sequence.

- _**Projection** methods:_
  - `chunk`: Splits the items of the collection into chunks of specified size.
  - `select`: Projects each item of the collection into a new form and returns an array that contains the transformed items of the collection.
  - `selectMany`: Projects each item of the collection to a collection and flattens the resulting collections into one collection.
  - `groupBy`: Groups the items of the collection according to a specified key selector function.
  - `join`: Correlates the items of two collection based on matching keys.

- _**Quantifier** methods:_
    - `all`: Determines whether all items of the collection satisfy a condition.
    - `any`: Determines whether the collection contains any items.

- _**Traversal** methods:_
    - `forEach`: Applies a callback function to each item of the collection.

> _Note: all collections implement the `JsonSerialize` and `ArrayIterator` interfaces. Collections also implement `ArrayAccess` to allow items to be accessed through the standard array syntax `$collection[$i]`, however **items can only be accessed** but **not set or unset**._



## License

_Types Collections_ is licensed under MIT license. See LICENSE file.



[ico-license]: https://img.shields.io/badge/license-MIT-brightgreen.svg
[ico-version]: https://img.shields.io/packagist/v/mediagone/types-collection.svg
[ico-downloads]: https://img.shields.io/packagist/dt/mediagone/types-collection.svg

[link-packagist]: https://packagist.org/packages/mediagone/types-collection
[link-downloads]: https://packagist.org/packages/mediagone/types-collection
