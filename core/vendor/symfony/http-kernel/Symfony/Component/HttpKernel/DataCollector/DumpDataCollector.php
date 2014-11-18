<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\HttpKernel\DataCollector;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Stopwatch\Stopwatch;
use Symfony\Component\VarDumper\Cloner\Data;
use Symfony\Component\VarDumper\Dumper\CliDumper;
use Symfony\Component\VarDumper\Dumper\HtmlDumper;
use Symfony\Component\VarDumper\Dumper\DataDumperInterface;

/**
 * @author Nicolas Grekas <p@tchwork.com>
 */
class DumpDataCollector extends DataCollector implements DataDumperInterface
{
    private $stopwatch;
    private $fileLinkFormat;
    private $dataCount = 0;
    private $isCollected = true;
    private $clonesCount = 0;
    private $clonesIndex = 0;
    private $rootRefs;

    public function __construct(Stopwatch $stopwatch = null, $fileLinkFormat = null)
    {
        $this->stopwatch = $stopwatch;
        $this->fileLinkFormat = $fileLinkFormat ?: ini_get('xdebug.file_link_format') ?: get_cfg_var('xdebug.file_link_format');

        // All clones share these properties by reference:
        $this->rootRefs = array(
            &$this->data,
            &$this->dataCount,
            &$this->isCollected,
            &$this->clonesCount,
        );
    }

    public function __clone()
    {
        $this->clonesIndex = ++$this->clonesCount;
    }

    public function dump(Data $data)
    {
        if ($this->stopwatch) {
            $this->stopwatch->start('dump');
        }
        if ($this->isCollected) {
            $this->isCollected = false;
        }

        $trace = PHP_VERSION_ID >= 50306 ? DEBUG_BACKTRACE_PROVIDE_OBJECT | DEBUG_BACKTRACE_IGNORE_ARGS : true;
        if (PHP_VERSION_ID >= 50400) {
            $trace = debug_backtrace($trace, 7);
        } else {
            $trace = debug_backtrace($trace);
        }

        $file = $trace[0]['file'];
        $line = $trace[0]['line'];
        $name = false;
        $fileExcerpt = false;

        for ($i = 1; $i < 7; ++$i) {
            if (isset($trace[$i]['class'], $trace[$i]['function'])
                && 'dump' === $trace[$i]['function']
                && 'Symfony\Component\VarDumper\VarDumper' === $trace[$i]['class']
            ) {
                $file = $trace[$i]['file'];
                $line = $trace[$i]['line'];

                while (++$i < 7) {
                    if (isset($trace[$i]['function']) && empty($trace[$i]['class']) && 'call_user_func' !== $trace[$i]['function']) {
                        $file = $trace[$i]['file'];
                        $line = $trace[$i]['line'];

                        break;
                    } elseif (isset($trace[$i]['object']) && $trace[$i]['object'] instanceof \Twig_Template) {
                        $info = $trace[$i]['object'];
                        $name = $info->getTemplateName();
                        $src = $info->getEnvironment()->getLoader()->getSource($name);
                        $info = $info->getDebugInfo();
                        if (isset($info[$trace[$i-1]['line']])) {
                            $file = false;
                            $line = $info[$trace[$i-1]['line']];
                            $src = explode("\n", $src);
                            $fileExcerpt = array();

                            for ($i = max($line - 3, 1), $max = min($line + 3, count($src)); $i <= $max; ++$i) {
                                $fileExcerpt[] = '<li'.($i === $line ? ' class="selected"' : '').'><code>'.htmlspecialchars($src[$i - 1]).'</code></li>';
                            }

                            $fileExcerpt = '<ol start="'.max($line - 3, 1).'">'.implode("\n", $fileExcerpt).'</ol>';
                        }
                        break;
                    }
                }
                break;
            }
        }

        if (false === $name) {
            $name = strtr($file, '\\', '/');
            $name = substr($file, strrpos($file, '/') + 1);
        }

        $this->data[] = compact('data', 'name', 'file', 'line', 'fileExcerpt');
        ++$this->dataCount;

        if ($this->stopwatch) {
            $this->stopwatch->stop('dump');
        }
    }

    public function collect(Request $request, Response $response, \Exception $exception = null)
    {
    }

    public function serialize()
    {
        if ($this->clonesCount !== $this->clonesIndex) {
            return 'a:0:{}';
        }

        $ser = serialize($this->data);
        $this->data = array();
        $this->dataCount = 0;
        $this->isCollected = true;

        return $ser;
    }

    public function unserialize($data)
    {
        parent::unserialize($data);
        $this->dataCount = count($this->data);
        self::__construct($this->stopwatch);
    }

    public function getDumpsCount()
    {
        return $this->dataCount;
    }

    public function getDumps($format, $maxDepthLimit = -1, $maxItemsPerDepth = -1)
    {
        if ('html' === $format) {
            $dumper = new HtmlDumper();
        } else {
            throw new \InvalidArgumentException(sprintf('Invalid dump format: %s', $format));
        }
        $dumps = array();

        foreach ($this->data as $dump) {
            $data = '';
            $dumper->dump(
                $dump['data']->getLimitedClone($maxDepthLimit, $maxItemsPerDepth),
                function ($line, $depth) use (&$data) {
                    if (-1 !== $depth) {
                        $data .= str_repeat('  ', $depth).$line."\n";
                    }
                }
            );
            $dump['data'] = $data;
            $dumps[] = $dump;
        }

        return $dumps;
    }

    public function getName()
    {
        return 'dump';
    }

    public function __destruct()
    {
        if (0 === $this->clonesCount-- && !$this->isCollected && $this->data) {
            $this->clonesCount = 0;
            $this->isCollected = true;

            $h = headers_list();
            $i = count($h);
            array_unshift($h, 'Content-Type: '.ini_get('default_mimetype'));
            while (0 !== stripos($h[$i], 'Content-Type:')) {
                --$i;
            }

            if ('cli' !== PHP_SAPI && stripos($h[$i], 'html')) {
                echo '<meta http-equiv="Content-Type" content="text/html; charset=utf-8">';
                $dumper = new HtmlDumper('php://output');
            } else {
                $dumper = new CliDumper('php://output');
                $dumper->setColors(false);
            }

            foreach ($this->data as $i => $dump) {
                $this->data[$i] = null;

                if ($dumper instanceof HtmlDumper) {
                    $dump['name'] = htmlspecialchars($dump['name'], ENT_QUOTES, 'UTF-8');
                    $dump['file'] = htmlspecialchars($dump['file'], ENT_QUOTES, 'UTF-8');
                    if ('' !== $dump['file']) {
                        if ($this->fileLinkFormat) {
                            $link = strtr($this->fileLinkFormat, array('%f' => $dump['file'], '%l' => $dump['line']));
                            $dump['name'] = sprintf('<a href="%s" title="%s">%s</a>', $link, $dump['file'], $dump['name']);
                        } else {
                            $dump['name'] = sprintf('<abbr title="%s">%s</abbr>', $dump['file'], $dump['name']);
                        }
                    }
                    echo "\n<span class=\"sf-dump-meta\">{$dump['name']} on line {$dump['line']}:</span>";
                } else {
                    echo "{$dump['name']} on line {$dump['line']}:\n";
                }
                $dumper->dump($dump['data']);
            }

            $this->data = array();
            $this->dataCount = 0;
        }
    }
}
