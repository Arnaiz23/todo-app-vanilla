<?php declare(strict_types=1);

namespace Lkrms\Curler\Exception;

use Lkrms\Curler\Curler;
use Lkrms\Facade\Format;

/**
 * Base class for Curler exceptions
 */
abstract class CurlerException extends \Lkrms\Exception\Exception
{
    /**
     * @var Curler
     */
    protected $Curler;

    public function __construct(string $message, Curler $curler)
    {
        $this->Curler = clone $curler;

        parent::__construct($message);
    }

    public function getDetail(): array
    {
        return [
            'Response' =>
                implode("\n", [
                    Format::array($this->Curler->ResponseHeadersByName ?: []) ?: '<no headers>',
                    $this->Curler->ResponseBody === null
                        ? '<no body>'
                        : ($this->Curler->ResponseBody === ''
                            ? '<empty body>'
                            : $this->Curler->ResponseBody),
                ]),
            'Request' =>
                is_array($this->Curler->Body)
                    ? Format::array($this->Curler->Body)
                    : ($this->Curler->Body === null
                        ? '<no body>'
                        : ($this->Curler->Body === ''
                            ? '<empty body>'
                            : $this->Curler->Body)),
            'curl_getinfo' =>
                $this->Curler->CurlInfo === null
                    ? '<not available>'
                    : Format::array(array_map(
                        fn($value) => is_string($value) ? trim($value) : $value,
                        $this->Curler->CurlInfo,
                    )),
        ];
    }
}
