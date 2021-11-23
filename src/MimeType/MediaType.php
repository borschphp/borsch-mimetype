<?php
/**
 * @author Alexandre DEBUSSCHÈRE <alexandre@kosmonaft.dev>
 */

namespace Borsch\MimeType;

/**
 * Class MediaType
 * @package Borsch\MimeType
 */
class MediaType extends MimeType
{

    const ALL = '*/*';
    const APPLICATION_ATOM_XML = 'application/atom+xml';
    const APPLICATION_CBOR = 'application/cbor';
    const APPLICATION_FORM_URLENCODED = 'application/x-www-form-urlencoded';
    const APPLICATION_JSON = 'application/json';
    const APPLICATION_OCTET_STREAM = 'application/octet-stream';
    const APPLICATION_PDF = 'application/pdf';
    const APPLICATION_PROBLEM_JSON = 'application/problem+json';
    const APPLICATION_PROBLEM_XML = 'plication/problem+xml';
    const APPLICATION_RSS_XML = 'application/rss+xml';
    const APPLICATION_NDJSON = 'application/x-ndjson';
    const APPLICATION_STREAM_JSON = 'application/stream+json';
    const APPLICATION_XHTML_XML = 'application/xhtml+xml';
    const APPLICATION_XML = 'application/xml';
    const IMAGE_GIF = 'image/gif';
    const IMAGE_JPEG = 'image/jpeg';
    const IMAGE_PNG = 'image/png';
    const MULTIPART_FORM_DATA = 'multipart/form-data';
    const MULTIPART_MIXED = 'multipart/mixed';
    const MULTIPART_RELATED = 'multipart/related';
    const TEXT_EVENT_STREAM = 'text/event-stream';
    const TEXT_HTML = 'text/html';
    const TEXT_MARKDOWN = 'text/markdown';
    const TEXT_PLAIN = 'text/plain';
    const TEXT_XML = 'text/xml';

    /**
     * @return float
     */
    public function getQualityValue(): float
    {
        $quality = $this->getParameter('q');
        return $quality != null ? (float)$this->unquote($quality) : 1.0;
    }

    /**
     * @param MediaType $media_type
     * @return MediaType
     */
    public function copyQualityValue(MediaType $media_type): MediaType
    {
        if (!$media_type->getParameter('q')) {
            return $this;
        }

        $parameters = $this->getParameters();
        $parameters['q'] = $media_type->getParameter('q');

        return new MediaType($this->getType(), $this->getSubtype(), $parameters);
    }

    /**
     * @return MediaType
     */
    public function removeQualityValue(): MediaType
    {
        if (!$this->getParameter('q')) {
            return $this;
        }

        $parameters = $this->getParameters();
        unset($parameters['q']);

        return new MediaType($this->getType(), $this->getSubtype(), $parameters);
    }

    /**
     * @param string $parameter
     * @param string $value
     */
    protected function validateParameters(string $parameter, string $value): void
    {
        parent::validateParameters($parameter, $value);
        if ($parameter == 'q') {
            $value = (float)$this->unquote($value);
            if ($value < 0 || $value > 1) {
                throw new \InvalidArgumentException(sprintf(
                    'Invalid quality value "%s", should be between 0.0 and 1.0.',
                    $value
                ));
            }
        }
    }
}
