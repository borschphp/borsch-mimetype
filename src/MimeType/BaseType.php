<?php
/**
 * @author Alexandre DEBUSSCHÈRE <alexandre@kosmonaft.dev>
 */

namespace Borsch\MimeType;

/**
 * Class BaseType
 * @package Borsch\MimeType
 */
class BaseType
{

    /** @var string */
    protected $type;

    /** @var string */
    protected $subtype;

    /** @var string[] */
    protected $parameters;

    /** @var string */
    protected $charset;

    /** @var string[] */
    protected $character_set = [];

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

        $this->setCharacterSet();
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
     * Character set as defined in RFC 2616 section 2.2.
     *
     * Basically, 1*<any CHAR except CTLs or separators>.
     *
     * CTL = <any US-ASCII control character (octets 0 - 31) and DEL (127)>
     * separators = "(" | ")" | "<" | ">" | "@" | "," | ";" | ":" | "\" | <"> | "/" | "[" | "]" | "?" | "=" |
     * "{" | "}" | SP | HT
     * SP = <US-ASCII SP, space (32)>
     * HT = <US-ASCII HT, horizontal-tab (9)>
     *
     * @return void
     * @see https://datatracker.ietf.org/doc/html/rfc2616#section-2.2
     */
    private function setCharacterSet()
    {
        $exceptions = array_merge(
            range(0, 31), // CTLs
            [127], // CTL
            array_map('ord', [ // separators
                '(',
                ')',
                '<',
                '>',
                '@',
                ',',
                ';',
                ':',
                '\'',
                '"',
                '/',
                '[',
                ']',
                '?',
                '=',
                '{',
                '}'
            ]),
            [32, 9] // separators
        );

        for ($i = 0; $i <= 127; $i += 1) {
            if (!in_array($i, $exceptions)) {
                $this->character_set[] = $i;
            }
        }
    }

    /**
     * @param string $type
     * @return void
     * @throws \InvalidArgumentException
     */
    private function validateType(string $type): void
    {
        foreach (str_split($type) as $char) {
            if (!in_array(ord($char), $this->character_set)) {
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
}
