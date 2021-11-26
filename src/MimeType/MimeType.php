<?php
/**
 * @author Alexandre DEBUSSCHÈRE <alexandre@kosmonaft.dev>
 */

namespace Borsch\MimeType;

/**
 * Class MimeType
 * @package Borsch\MimeType
 */
class MimeType extends BaseType
{

    /**
     * @param string $mimetype
     * @return MimeType
     */
    public static function createFromString(string $mimetype): MimeType
    {
        if (!strlen($mimetype)) {
            throw new \InvalidArgumentException('Provided MimeType cannot be empty.');
        }

        $parts = explode(';', $mimetype);
        $parts = array_map('trim', $parts);

        $types = array_shift($parts);
        $parameters = [];

        $types = explode('/', $types);
        if (count($types) != 2) {
            throw new \InvalidArgumentException('MimeType does not have a type or subtype.');
        }

        $type = $types[0];
        $subtype = $types[1];

        if ($type == '*' && $subtype != '*') {
            throw new \InvalidArgumentException('Wildcard type is legal only in "*/*" (all mime types)');
        }

        foreach ($parts as $part) {
            [$key, $value] = explode('=', $part);
            $parameters[$key] = $value;
        }

        return new MimeType($type, $subtype, $parameters);
    }

    /**
     * Indicate whether this MIME Type includes the given MIME Type.
     *
     * For instance, text/* includes text/plain and text/html,
     * and application/*+xml includes application/soap+xml, etc.
     * This method is not symmetric.
     *
     * @param MimeType $other
     * @return bool
     */
    public function includes(MimeType $other): bool
    {
        $pattern = sprintf('%s/%s', $this->getType(), $this->getSubtype());
        $filename = sprintf('%s/%s', $other->getType(), $other->getSubtype());

        return fnmatch($pattern, $filename);
    }

    /**
     * Indicate whether this MIME Type is compatible with the given MIME Type.
     *
     * For instance, text/* is compatible with text/plain, text/html, and vice versa.
     * In effect, this method is similar to includes, except that it is symmetric.
     *
     * @param MimeType $other
     * @return bool
     */
    public function isCompatibleWith(MimeType $other): bool
    {
        $pattern = sprintf('%s/%s', $this->getType(), $this->getSubtype());
        $filename = sprintf('%s/%s', $other->getType(), $other->getSubtype());

        if (!$this->isWildcardSubtype() && $other->isWildcardSubtype()) {
            // fnmatch does not work if wildcard is placed on second parameter, so we swap
            $swap = $pattern;
            $pattern = $filename;
            $filename = $swap;
        }

        return fnmatch($pattern, $filename);
    }

    /**
     * @param MimeType $other
     * @return bool
     */
    public function equals(MimeType $other): bool
    {
        if ($this == $other) {
            return true;
        }

        return strtolower($this->type) == strtolower($other->type) &&
            strtolower($this->subtype) == strtolower($other->subtype) &&
            $this->parametersAreEqual($other);
    }

    /**
     * @param MimeType $other
     * @return bool
     */
    public function equalsTypeAndSubtype(MimeType $other): bool
    {
        return strtolower($this->type) == strtolower($other->type) &&
            strtolower($this->subtype) == strtolower($other->subtype);
    }

    /**
     * @param MimeType[] $mime_types
     * @return bool
     */
    public function isIn(array $mime_types): bool
    {
        foreach ($mime_types as $key => $mime_type) {
            if (!$mime_type instanceof MimeType) {
                throw new \InvalidArgumentException(sprintf(
                    'Record at index "%s" is not an instance of %s, found %s instead.',
                    $key,
                    MimeType::class,
                    is_object($mime_type) ? get_class($mime_type) : gettype($mime_type)
                ));
            }

            if ($mime_type->equalsTypeAndSubtype($this)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param MimeType $other
     * @return bool
     */
    private function parametersAreEqual(MimeType $other): bool
    {
        if (count($this->parameters) != count($other->parameters)) {
            return false;
        }

        foreach ($this->parameters as $name => $value) {
            if (!isset($other->parameters[$name]) || $value != $other->parameters[$name]) {
                return false;
            }
        }

        return true; // @codeCoverageIgnore
    }
}
