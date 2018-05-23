<?php

namespace Karl\Acme2\Http;

use Psr\Http\Message\StreamInterface;
use RuntimeException;

/**
 * Class Stream
 *
 * dumb PSR-7 stream class, can handle stream strings only
 *
 * @package Karl\Acme2\Http
 */
class Stream implements StreamInterface
{
    /**
     * @var resource
     */
    protected $stream;

    protected $size = null;

    public function __construct($string)
    {
        $this->attach($string);
    }

    /**
     * Reads all data from the stream into a string, from the beginning to end.
     *
     * This method MUST attempt to seek to the beginning of the stream before
     * reading data and read the stream until the end is reached.
     *
     * Warning: This could attempt to load a large amount of data into memory.
     *
     * This method MUST NOT raise an exception in order to conform with PHP's
     * string casting operations.
     *
     * @see http://php.net/manual/en/language.oop5.magic.php#object.tostring
     * @return string
     */
    public function __toString()
    {
        return stream_get_contents($this->stream);
    }

    /**
     * Closes the stream and any underlying resources.
     *
     * @return void
     */
    public function close()
    {
        fclose($this->stream);
        $this->detach();
    }

    /**
     * Separates any underlying resources from the stream.
     *
     * After the stream has been detached, the stream is in an unusable state.
     *
     * @return resource|null Underlying PHP stream, if any
     */
    public function detach()
    {
        $oldStream    = $this->stream;
        $this->stream = null;
        $this->size   = null;

        return $oldStream;
    }

    /**
     * attach the string stream
     *
     * @param $string
     *
     * @return resource Underlying PHP stream, if any
     */
    protected function attach($string)
    {
        $this->stream = fopen('php://memory', 'r+');
        $this->size = null;
        if (strlen($string))
        {
            fwrite($this->stream, $string);
            rewind($this->stream);
            $this->size = strlen($string);
        }
        return $this->stream;
    }

    /**
     * whether string stream is attached or not
     *
     * @return bool
     */
    protected function isAttached()
    {
        return $this->stream !== null;
    }


    /**
     * Get the size of the stream if known.
     *
     * @return int|null Returns the size in bytes if known, or null if unknown.
     */
    public function getSize()
    {
        if ($this->isAttached() && $this->size === null)
        {
            $stats      = fstat($this->stream);
            $this->size = isset($stats['size']) ? $stats['size'] : null;
        }

        return $this->size;
    }

    /**
     * Returns the current position of the file read/write pointer
     *
     * @return int Position of the file pointer
     * @throws \RuntimeException on error.
     */
    public function tell()
    {
        if (!$this->isAttached() || ($pos = ftell($this->stream)) === false)
            throw new RuntimeException('Unable to get current stream position.');

        return $pos;
    }

    /**
     * Returns true if the stream is at the end of the stream.
     *
     * @return bool
     */
    public function eof()
    {
        if (!$this->isAttached())
            return true;

        return feof($this->stream);
    }

    /**
     * Returns whether or not the stream is seekable.
     *
     * @return bool
     */
    public function isSeekable()
    {
        // assuming that string streams are always seekable
        return true;
    }

    /**
     * Seek to a position in the stream.
     *
     * @link http://www.php.net/manual/en/function.fseek.php
     *
     * @param int $offset Stream offset
     * @param int $whence Specifies how the cursor position will be calculated
     *     based on the seek offset. Valid values are identical to the built-in
     *     PHP $whence values for `fseek()`.  SEEK_SET: Set position equal to
     *     offset bytes SEEK_CUR: Set position to current location plus offset
     *     SEEK_END: Set position to end-of-stream plus offset.
     *
     * @throws \RuntimeException on failure.
     */
    public function seek($offset, $whence = SEEK_SET)
    {
        if (!$this->isAttached() || fseek($this->stream, $offset, $whence) === -1)
            throw new RuntimeException('Unable to seek in the stream.');
    }

    /**
     * Seek to the beginning of the stream.
     *
     * If the stream is not seekable, this method will raise an exception;
     * otherwise, it will perform a seek(0).
     *
     * @see seek()
     * @link http://www.php.net/manual/en/function.fseek.php
     * @throws \RuntimeException on failure.
     */
    public function rewind()
    {
        if (!$this->isAttached() || rewind($this->stream) === false)
            throw new RuntimeException('Unable to rewind the stream.');
    }

    /**
     * Returns whether or not the stream is writable.
     *
     * @return bool
     */
    public function isWritable()
    {
        if (!$this->isAttached())
            return false;

        // our dumb string streams are always writable
        return true;
    }

    /**
     * Write data to the stream.
     *
     * @param string $string The string that is to be written.
     *
     * @return int Returns the number of bytes written to the stream.
     * @throws \RuntimeException on failure.
     */
    public function write($string)
    {
        if (!$this->isAttached() || ($written = fwrite($this->stream, $string)) === false)
            throw new RuntimeException('Unable to write to stream.');

        // force size recalculation
        $this->size = null;

        return $written;
    }

    /**
     * Returns whether or not the stream is readable.
     *
     * @return bool
     */
    public function isReadable()
    {
        if (!$this->isAttached())
            return false;

        // our dumb string streams are always readable
        return true;
    }

    /**
     * Read data from the stream.
     *
     * @param int $length Read up to $length bytes from the object and return
     *     them. Fewer than $length bytes may be returned if underlying stream
     *     call returns fewer bytes.
     *
     * @return string Returns the data read from the stream, or an empty string
     *     if no bytes are available.
     * @throws \RuntimeException if an error occurs.
     */
    public function read($length)
    {
        if (!$this->isAttached() || ($data = fread($this->stream, $length)) === false)
            throw new RuntimeException('Unable to read from stream.');

        return $data;

    }

    /**
     * Returns the remaining contents in a string
     *
     * @return string
     * @throws \RuntimeException if unable to read or an error occurs while
     *     reading.
     */
    public function getContents()
    {
        if (!$this->isAttached() || ($contents = stream_get_contents($this->stream)) === false)
            throw new RuntimeException('Unable to get stream contents.');

        return $contents;
    }

    /**
     * Get stream metadata as an associative array or retrieve a specific key.
     *
     * The keys returned are identical to the keys returned from PHP's
     * stream_get_meta_data() function.
     *
     * @link http://php.net/manual/en/function.stream-get-meta-data.php
     *
     * @param string $key Specific metadata to retrieve.
     *
     * @return array|mixed|null Returns an associative array if no key is
     *     provided. Returns a specific key value if a key is provided and the
     *     value is found, or null if the key is not found.
     */
    public function getMetadata($key = null)
    {
        if (!$this->isAttached())
            return null;

        $meta = stream_get_meta_data($this->stream);
        if (is_null($key))
        {
            return $meta;
        }

        return isset($this->meta[$key]) ? $meta[$key] : null;
    }
}