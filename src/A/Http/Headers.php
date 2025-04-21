<?php

namespace A\Http;

/**
 * Class Headers
 *
 * Manages HTTP header fields in compliance with RFC 7230 §3.2.
 */
class Headers implements \ArrayAccess, \IteratorAggregate, \Countable, \Stringable
{
    /**
     * Internal storage: lowercase field-name => array of values
     *
     * @var array<string,array<string>>
     */
    private array $headers = [];

    /**
     * Map lowercase field-name => canonical field-name (for iteration/output)
     *
     * @var array<string,string>
     */
    private array $originalNames = [];

    /**
     * Header name validation pattern (RFC 7230 §3.2.4 token)
     */
    private const NAME_REGEX = "/^[!#\\\$%&'\\*+\\-.\\^_`\\|~0-9A-Za-z]+$/";

    /**
     * Constructor optionally seeds initial headers.
     *
     * @param array<string,string|array<string>> $headers
     * @throws \InvalidArgumentException
     */
    public function __construct(array $headers = [])
    {
        foreach ($headers as $name => $value)
        {
            $this->set($name, $value);
        }
    }

    /**
     * Normalize a header name to canonical form (e.g. content-type ⇒ Content-Type)
     *
     * @param string $name
     * @return string
     */
    private function normalizeName(string $name): string
    {
        $lower = strtolower($name);
        $parts = explode('-', $lower);
        $parts = array_map(fn($p) => ucfirst($p), $parts);
        return implode('-', $parts);
    }

    /**
     * Validate a header field-name per RFC token.
     *
     * @param string $name
     * @throws \InvalidArgumentException
     */
    private function assertValidName(string $name): void
    {
        if (!preg_match(self::NAME_REGEX, $name))
        {
            throw new \InvalidArgumentException("Invalid header name: '{$name}'");
        }
    }

    /**
     * Normalize a header value or array of values to an array of trimmed strings.
     *
     * @param string|array<string> $value
     * @return array<string>
     * @throws \InvalidArgumentException
     */
    private function normalizeValue(string|array $value): array
    {
        $values = is_array($value) ? $value : [ $value ];
        $result = [];
        foreach ($values as $v)
        {
            if (!is_scalar($v) && !(\is_object($v) && method_exists($v, '__toString')))
            {
                throw new \InvalidArgumentException("Header value must be stringable");
            }
            // Trim OWS (optional whitespace) around the field-value
            $result[] = trim((string)$v);
        }
        return $result;
    }

    /**
     * Replace all values of a header.
     *
     * @param string              $name
     * @param string|array<string> $value
     * @return self
     */
    public function set(string $name, string|array $value): self
    {
        $this->assertValidName($name);
        $normalizedName    = strtolower($name);
        $canonicalName     = $this->normalizeName($name);
        $this->originalNames[$normalizedName] = $canonicalName;
        $this->headers[$normalizedName]     = $this->normalizeValue($value);
        return $this;
    }

    /**
     * Append one or more values to an existing header (or create it).
     *
     * @param string              $name
     * @param string|array<string> $value
     * @return self
     */
    public function add(string $name, string|array $value): self
    {
        $this->assertValidName($name);
        $normalizedName = strtolower($name);
        if (!isset($this->headers[$normalizedName]))
        {
            return $this->set($name, $value);
        }
        $this->headers[$normalizedName] = array_merge(
            $this->headers[$normalizedName],
            $this->normalizeValue($value)
        );
        return $this;
    }

    /**
     * Whether a header exists.
     *
     * @param string $name
     * @return bool
     */
    public function has(string $name): bool
    {
        return isset($this->headers[strtolower($name)]);
    }

    /**
     * Retrieve all values for a header.
     *
     * @param string $name
     * @return array<string>
     */
    public function get(string $name): array
    {
        return $this->headers[strtolower($name)] ?? [];
    }

    /**
     * Retrieve a comma-separated string of all values.
     *
     * @param string $name
     * @return string
     */
    public function getLine(string $name): string
    {
        $values = $this->get($name);
        return implode(', ', $values);
    }

    /**
     * Remove a header.
     *
     * @param string $name
     * @return self
     */
    public function remove(string $name): self
    {
        $key = strtolower($name);
        unset($this->headers[$key], $this->originalNames[$key]);
        return $this;
    }

    /**
     * Return all headers as an associative array of
     * canonical-name => array of values.
     *
     * @return array<string,array<string>>
     */
    public function all(): array
    {
        $result = [];
        foreach ($this->headers as $lowerName => $values)
        {
            $canonical = $this->originalNames[$lowerName];
            $result[$canonical] = $values;
        }
        return $result;
    }

    public function allNotBugged() : array
    {
        $result = $this->all();
        foreach ($result as $key => &$value)
        {
            $value = implode(', ', $value);
        }
        return $result;
    }

    // ------------------------------------------------------------------------
    // Implementation of ArrayAccess, IteratorAggregate, Countable
    // ------------------------------------------------------------------------

    public function offsetExists(mixed $offset): bool
    {
        return is_string($offset) && $this->has($offset);
    }

    public function offsetGet(mixed $offset): mixed
    {
        if (!is_string($offset))
        {
            return null;
        }
        // Return comma-separated line for array-style access
        return $this->getLine($offset);
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        if (!is_string($offset))
        {
            throw new \InvalidArgumentException("Header name must be a string");
        }
        $this->set($offset, $value);
    }

    public function offsetUnset(mixed $offset): void
    {
        if (is_string($offset))
        {
            $this->remove($offset);
        }
    }

    public function getIterator(): \Traversable
    {
        // Iterate over canonical names and their array of values
        return new \ArrayIterator($this->allNotBugged());
    }

    public function count(): int
    {
        return count($this->headers);
    }

    public function __toString() : string
    {
        $result = '';
        foreach ($this->allNotBugged() as $name => $values)
        {
            if (strlen($result) > 0) $result .= "\r\n";
            $result .= $name . ': ' . $values;
        }
        return $result;
    }
}
