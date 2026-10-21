<?php

namespace AmjadIqbal\NotificationInbox\Support;

use BackedEnum;
use Closure;
use DateTimeInterface;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use JsonSerializable;
use ReflectionMethod;
use ReflectionObject;
use Throwable;
use UnitEnum;

/**
 * Turns whatever a toX() method returned into plain JSON-safe data.
 *
 * Message objects are read the way their own channel reads them: toArray() or
 * jsonSerialize() when they have one, otherwise their properties. HTTP clients
 * that some messages carry (FcmMessage::usingClient(), VonageMessage::$client)
 * are never walked into, and Eloquent models are reduced to type and key so a
 * user's whole row never lands in the inbox.
 */
final class PayloadSerializer
{
    private const MAX_DEPTH = 10;

    /** Properties that hold a transport, never message content. */
    private const SKIP_PROPERTIES = ['client', 'http', 'httpClient', 'guzzle'];

    public function serialize(mixed $value, int $depth = 0): mixed
    {
        if ($depth > self::MAX_DEPTH) {
            return '[too deep]';
        }

        return match (true) {
            $value === null, is_scalar($value) => $value,
            is_array($value) => $this->serializeArray($value, $depth),
            $value instanceof BackedEnum => $value->value,
            $value instanceof UnitEnum => $value->name,
            $value instanceof DateTimeInterface => $value->format(DateTimeInterface::ATOM),
            $value instanceof Closure => '[closure]',
            $value instanceof Model => ['model' => $value::class, 'key' => $this->serialize($value->getKey(), $depth + 1)],
            $value instanceof Collection => $this->serializeArray($value->all(), $depth),
            is_object($value) => $this->serializeObject($value, $depth),
            default => '['.get_debug_type($value).']',
        };
    }

    /**
     * @param  array<array-key, mixed>  $value
     * @return array<array-key, mixed>
     */
    private function serializeArray(array $value, int $depth): array
    {
        $out = [];

        foreach ($value as $key => $item) {
            $out[$key] = $this->serialize($item, $depth + 1);
        }

        return $out;
    }

    private function serializeObject(object $value, int $depth): mixed
    {
        if ($this->isTransport($value)) {
            return '['.$value::class.']';
        }

        try {
            if ($this->hasCallable($value, 'toArray')) {
                return $this->serialize($value->toArray(), $depth + 1);
            }

            if ($value instanceof JsonSerializable) {
                return $this->serialize($value->jsonSerialize(), $depth + 1);
            }

            if ($value instanceof Arrayable) {
                return $this->serialize($value->toArray(), $depth + 1);
            }
        } catch (Throwable) {
            // A message whose toArray() needs more setup than we have: read its properties instead.
        }

        if ($value instanceof Htmlable) {
            return $value->toHtml();
        }

        $properties = [];

        foreach ((new ReflectionObject($value))->getProperties() as $property) {
            if ($property->isStatic() || ! $property->isInitialized($value)) {
                continue;
            }

            if (in_array($property->getName(), self::SKIP_PROPERTIES, true)) {
                continue;
            }

            $properties[$property->getName()] = $this->serialize($property->getValue($value), $depth + 1);
        }

        if ($properties === [] && method_exists($value, '__toString')) {
            return (string) $value;
        }

        return $properties;
    }

    private function hasCallable(object $value, string $method): bool
    {
        if (! method_exists($value, $method)) {
            return false;
        }

        $reflection = new ReflectionMethod($value, $method);

        return $reflection->isPublic() && ! $reflection->isStatic() && $reflection->getNumberOfRequiredParameters() === 0;
    }

    private function isTransport(object $value): bool
    {
        foreach (['Psr\Http\Client\ClientInterface', 'GuzzleHttp\ClientInterface', 'Illuminate\Http\Client\Factory', 'Illuminate\Http\Client\PendingRequest'] as $interface) {
            if (is_a($value, $interface)) {
                return true;
            }
        }

        return false;
    }
}
