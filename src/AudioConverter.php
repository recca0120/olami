<?php
/**
 * Created by PhpStorm.
 * User: recca0120
 * Date: 2018/11/15
 * Time: 2:51 PM.
 */

namespace Recca0120\Olami;

use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;

class AudioConverter
{
    /**
     * @var \Symfony\Component\Process\ExecutableFinder
     */
    private $executableFinder;

    /**
     * AudioConverter constructor.
     *
     * @param \Symfony\Component\Process\ExecutableFinder $executableFinder
     */
    public function __construct(ExecutableFinder $executableFinder = null)
    {
        $this->executableFinder = $executableFinder ?: new ExecutableFinder();
    }

    public function convert($file)
    {
        $binary = $this->executableFinder->find('ffmpeg', '/usr/bin/ffmpeg', [
            '/usr/bin',
            '/usr/local/bin'
        ]);

        if ($binary) {
            $extension = pathinfo($file, PATHINFO_EXTENSION);
            $source = str_replace('.'.$extension, 'source.'. $extension, $file);
            copy($file, $source);

            $process = new Process([$binary, '-i', $source, '-acodec', 'pcm_s16le', '-ac', '1', '-ar', 16000, $file, '-y']);
            $process->mustRun();

            unlink($source);
        }

        return $file;
    }
}
