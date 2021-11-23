<?php
/**
 * @author Alexandre DEBUSSCHÈRE <alexandre@kosmonaft.dev>
 */

namespace Borsch\MimeType;

/**
 * Class MimeType
 * @package Borsch\MimeType
 */
class MimeType
{

    /** @var string */
    private $type;

    /** @var string */
    private $subtype;

    /** @var string[] */
    private $parameters;

    /** @var string */
    private $charset;

    /** @var string[] */
    private $character_set = [
        '0', '1', '2', '3', '4', '5', '6', '7', '8', '9',
        'a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm', 'n', 'o', 'p', 'q', 'r', 's', 't', 'u', 'v', 'w', 'x', 'y', 'z',
        'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z',
        '(', ')', '<', '>', '@', ',', ';', ':', '\\', '\"', '/', '[', ']', '?', '=', '{', '}', ' ', '\t', '*', '+', '-', '_', '.'
    ];

    /**
     * @param string $type
     * @param string $subtype
     * @param string[] $parameters
     */
    public function __construct(string $type = '*', string $subtype = '*', array $parameters = [])
    {
        if (!strlen($type)) {
            throw new \InvalidArgumentException('Parameter "type" must not be empty.');
        }

        if (!strlen($subtype)) {
            throw new \InvalidArgumentException('Parameter "subtype" must not be empty.');
        }

        $this->ValidateType($type);
        $this->ValidateType($subtype);

        $this->type = $type;
        $this->subtype = $subtype;

        foreach ($parameters as $parameter => $value) {
            $this->validateParameters($parameter, $value);
        }

        $this->parameters = $parameters;
    }

    /**
     * @param string $mimetype
     * @return MimeType
     */
    public static function createFromString(string $mimetype): MimeType
    {
        if (!strlen($mimetype)) {
            throw new \InvalidArgumentException('Provided MimeType cannot be empty.');
        }

        $index = strpos($mimetype, ';');
        $full_type = trim($index !== false ? substr($mimetype, 0, $index) : $mimetype);

        if (!strlen($full_type)) {
            throw new \InvalidArgumentException('MimeType must not be empty.');
        }

        if ($full_type == '*') {
            $full_type = '*/*';
        }

        $sub_index = strpos($full_type, '/');
        if ($sub_index === false) {
            throw new \InvalidArgumentException('Provided MimeType does not contain "/".');
        }
        if ($sub_index == strlen($full_type) - 1) {
            throw new \InvalidArgumentException('Provided MimeType does not contain subtype after "/".');
        }

        $type = substr($full_type, 0, $sub_index);
        $subtype = substr($full_type, $sub_index + 1);
        if ($type == '*' && $subtype != '*') {
            throw new \InvalidArgumentException('Wildcard type is legal only in "*/*" (all mime types)');
        }

        $parameters = [];
        if ($index < strlen($mimetype)) {
            $params = explode(';', substr($mimetype, $index + 1));
            foreach ($params as $param) {
                $key_value = explode('=', $param);
                if (count($key_value) != 2) {
                    continue;
                }

                $key = trim($key_value[0]);
                $value = trim($key_value[1]);

                $parameters[$key] = $value;
            }
        }

        return new MimeType($type, $subtype, $parameters);
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        $mimetype = sprintf(
            '%s/%s',
            $this->type,
            $this->subtype
        );

        foreach ($this->parameters as $name => $value) {
            $mimetype .= sprintf(';%s=%s', $name, $value);
        }

        return $mimetype;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @return string
     */
    public function getSubtype(): string
    {
        return $this->subtype;
    }

    /**
     * @return string|null
     */
    public function getSubtypeSuffix(): ?string
    {
        $position = strpos($this->subtype, '+');
        if ($position !== false && strlen($this->subtype) > $position) {
            return substr($this->subtype, $position + 1);
        }

        return null;
    }

    /**
     * @return string[]
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }

    /**
     * @param string $name
     * @return string|null
     */
    public function getParameter(string $name): ?string
    {
        return $this->parameters[$name] ?? null;
    }

    /**
     * @return string
     */
    public function getCharset(): ?string
    {
        return $this->charset;
    }

    /**
     * @return bool
     */
    public function isWildcardType(): bool
    {
        return $this->type == '*';
    }

    /**
     * @return bool
     */
    public function isWildcardSubtype(): bool
    {
        return $this->subtype == '*' || substr($this->subtype, 0, 2) == '*+';
    }

    /**
     * @return bool
     */
    public function isConcrete(): bool
    {
        return !$this->isWildcardType() && !$this->isWildcardSubtype();
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
        if ($this->isWildcardType()) {
            return true;
        }

        if ($this->type != $other->type) {
            return false;
        }

        if ($this->subtype == $other->subtype) {
            return true;
        }

        if ($this->isWildcardSubtype()) {
            $position = strpos($this->subtype, '+');
            if ($position === false) {
                return true;
            }

            $other_position = strpos($other->subtype, '+');

            if ($other_position !== false) {
                $subtype_without_suffix = substr($this->subtype, 0, $position);
                $subtype_suffix = substr($this->subtype, $position + 1);
                $other_subtype_suffix = substr($other->subtype, $other_position + 1);

                if ($subtype_suffix == $other_subtype_suffix && $subtype_without_suffix == '*') {
                    return true;
                }
            }
        }

        return false;
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
     * @param string $type
     * @return void
     * @throws \InvalidArgumentException
     */
    private function validateType(string $type): void
    {
        foreach (str_split($type) as $char) {
            if (!in_array($char, $this->character_set)) {
                throw new \InvalidArgumentException(sprintf(
                    'Invalid character "%s" in (sub)type "%s".',
                    $char,
                    $type
                ));
            }
        }
    }

    /**
     * @param string $parameter
     * @param string $value
     * @throws \InvalidArgumentException
     */
    protected function validateParameters(string $parameter, string $value): void
    {
        if (!strlen($parameter)) {
            throw new \InvalidArgumentException('Parameter must not be empty.');
        }

        if (!strlen($value)) {
            throw new \InvalidArgumentException('Parameter value must not be empty.');
        }

        $this->validateType($parameter);

        if (strtolower($parameter) == 'charset') {
            if ($this->charset == null) {
                $this->charset = $this->unquote($value);
            }
        } elseif (!$this->isQuotedString($value)) {
            $this->validateType($value);
        }
    }

    /**
     * @param string $str
     * @return bool
     */
    private function isQuotedString(string $str): bool
    {
        if (strlen($str) < 2) {
            return false;
        }

        return (substr($str, 0, 1) == '"' && substr($str, -1) == '"') ||
            (substr($str, 0, 1) == '\'' && substr($str, -1) == '\'');
    }

    /**
     * @param string $str
     * @return string
     */
    protected function unquote(string $str): string
    {
        return $this->isQuotedString($str) ?
            trim($str, '\'"') : $str;
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
            if (!isset($other->parameters[$name])) {
                return false;
            }

            if ($name == 'charset' && $this->charset != $other->charset || $value != $other->parameters[$name]) {
                return false;
            }
        }

        return true;
    }
}
