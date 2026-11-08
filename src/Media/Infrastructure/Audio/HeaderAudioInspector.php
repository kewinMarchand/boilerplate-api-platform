<?php

declare(strict_types=1);

namespace App\Media\Infrastructure\Audio;

use App\Media\Domain\AudioFormat;
use App\Media\Domain\AudioInspector;
use App\Media\Domain\SourceAudio;

/**
 * Lit le format et la durée d'un fichier audio dans ses en-têtes, en PHP seul (ADR 0010), sans décoder le son :
 * - MP3 : tag ID3v2 sauté, deux en-têtes de trame consécutifs exigés, nombre de trames de l'en-tête Xing, Info ou VBRI,
 *   sinon débit constant appliqué à la taille des données ;
 * - OGG (Vorbis ou Opus) : position de la dernière page divisée par la fréquence de l'en-tête d'identification ;
 * - M4A : boîte mvhd (durée et échelle de temps), au moins une piste son et aucune piste vidéo.
 */
final readonly class HeaderAudioInspector implements AudioInspector
{
    private const array MP3_BITRATES = [
        3 => [0, 32, 40, 48, 56, 64, 80, 96, 112, 128, 160, 192, 224, 256, 320],
        2 => [0, 8, 16, 24, 32, 40, 48, 56, 64, 80, 96, 112, 128, 144, 160],
    ];
    private const array MP3_SAMPLE_RATES = [3 => [44100, 48000, 32000], 2 => [22050, 24000, 16000], 0 => [11025, 12000, 8000]];
    private const int MP3_SCAN_BYTES = 65536;
    private const int OGG_TAIL_BYTES = 131072;
    private const int OPUS_SAMPLE_RATE = 48000;
    private const int MP4_MAX_MOOV_BYTES = 16 * 1024 * 1024;

    public function inspect(string $path): ?SourceAudio
    {
        $file = @fopen($path, 'r');
        if (false === $file) {
            return null;
        }
        try {
            $size = fstat($file)['size'] ?? 0;
            $head = (string) fread($file, 12);

            return match (true) {
                str_starts_with($head, 'OggS') => $this->ogg($file, $size),
                'ftyp' === substr($head, 4, 4) => $this->mp4($file, $size),
                default => $this->mp3($file, $size),
            };
        } finally {
            fclose($file);
        }
    }

    /**
     * @param resource $file
     */
    private function mp3($file, int $size): ?SourceAudio
    {
        fseek($file, 0);
        $offset = 0;
        $id3 = (string) fread($file, 10);
        if (10 === \strlen($id3) && str_starts_with($id3, 'ID3')) {
            $tagSize = ((\ord($id3[6]) & 0x7F) << 21) | ((\ord($id3[7]) & 0x7F) << 14) | ((\ord($id3[8]) & 0x7F) << 7) | (\ord($id3[9]) & 0x7F);
            $offset = 10 + $tagSize + ((\ord($id3[5]) & 0x10) ? 10 : 0);
        }
        fseek($file, $offset);
        $buffer = (string) fread($file, self::MP3_SCAN_BYTES);

        for ($i = 0, $end = \strlen($buffer) - 4; $i <= $end; ++$i) {
            if ("\xFF" !== $buffer[$i] || 0xE0 !== (\ord($buffer[$i + 1]) & 0xE0)) {
                continue;
            }
            $frame = $this->mp3Frame(substr($buffer, $i, 4));
            if (null === $frame) {
                continue;
            }
            $next = $i + $frame['length'];
            if ($next + 4 > \strlen($buffer) || null === $this->mp3Frame(substr($buffer, $next, 4))) {
                continue;
            }

            $frames = $this->mp3FrameCount(substr($buffer, $i, 64), $frame);
            if (null !== $frames) {
                return new SourceAudio(AudioFormat::Mp3, $frames * $frame['samples'] / $frame['sampleRate']);
            }
            $audioBytes = $size - $offset - $i - ($this->hasId3v1($file, $size) ? 128 : 0);

            return new SourceAudio(AudioFormat::Mp3, $audioBytes * 8 / ($frame['bitrate'] * 1000));
        }

        return null;
    }

    /**
     * En-tête de trame MPEG Layer III, ou null s'il n'en est pas un.
     *
     * @return array{version: int, mono: bool, bitrate: int, sampleRate: int, samples: int, length: int}|null
     */
    private function mp3Frame(string $header): ?array
    {
        if (4 !== \strlen($header) || "\xFF" !== $header[0]) {
            return null;
        }
        [$b1, $b2, $b3] = [\ord($header[1]), \ord($header[2]), \ord($header[3])];
        $version = ($b1 >> 3) & 0x03;
        $layer = ($b1 >> 1) & 0x03;
        $bitrateIndex = $b2 >> 4;
        $sampleRateIndex = ($b2 >> 2) & 0x03;
        if (0xE0 !== ($b1 & 0xE0) || 1 === $version || 1 !== $layer || 0 === $bitrateIndex || 15 === $bitrateIndex || 3 === $sampleRateIndex) {
            return null;
        }
        $bitrate = self::MP3_BITRATES[3 === $version ? 3 : 2][$bitrateIndex];
        $sampleRate = self::MP3_SAMPLE_RATES[$version][$sampleRateIndex];
        $mpeg1 = 3 === $version;

        return [
            'version' => $version,
            'mono' => 3 === ($b3 >> 6),
            'bitrate' => $bitrate,
            'sampleRate' => $sampleRate,
            'samples' => $mpeg1 ? 1152 : 576,
            'length' => intdiv(($mpeg1 ? 144 : 72) * $bitrate * 1000, $sampleRate) + (($b2 >> 1) & 0x01),
        ];
    }

    /**
     * Nombre de trames de l'en-tête Xing ou Info (placé après les informations annexes) ou VBRI (32 octets après l'en-tête).
     *
     * @param array{version: int, mono: bool, bitrate: int, sampleRate: int, samples: int, length: int} $frame
     */
    private function mp3FrameCount(string $start, array $frame): ?int
    {
        $sideInfo = 3 === $frame['version'] ? ($frame['mono'] ? 17 : 32) : ($frame['mono'] ? 9 : 17);
        $xing = substr($start, 4 + $sideInfo, 12);
        if (12 === \strlen($xing) && \in_array(substr($xing, 0, 4), ['Xing', 'Info'], true)) {
            $flags = self::uint32(substr($xing, 4, 4));
            $frames = self::uint32(substr($xing, 8, 4));
            if (1 === ($flags & 0x01) && $frames > 0) {
                return $frames;
            }
        }
        $vbri = substr($start, 36, 18);
        if (18 === \strlen($vbri) && str_starts_with($vbri, 'VBRI')) {
            $frames = self::uint32(substr($vbri, 14, 4));
            if ($frames > 0) {
                return $frames;
            }
        }

        return null;
    }

    /**
     * @param resource $file
     */
    private function hasId3v1($file, int $size): bool
    {
        if ($size < 128) {
            return false;
        }
        fseek($file, $size - 128);

        return 'TAG' === fread($file, 3);
    }

    /**
     * @param resource $file
     */
    private function ogg($file, int $size): ?SourceAudio
    {
        fseek($file, 0);
        $page = (string) fread($file, 512);
        if (\strlen($page) < 28) {
            return null;
        }
        $segments = \ord($page[26]);
        $packet = substr($page, 27 + $segments, 32);
        if (str_starts_with($packet, "\x01vorbis")) {
            $sampleRate = self::unpackInt('V', substr($packet, 12, 4), 4);
            $preSkip = 0;
        } elseif (str_starts_with($packet, 'OpusHead')) {
            $sampleRate = self::OPUS_SAMPLE_RATE;
            $preSkip = self::unpackInt('v', substr($packet, 10, 2), 2);
        } else {
            return null;
        }
        if ($sampleRate < 1) {
            return null;
        }

        $tailStart = max(0, $size - self::OGG_TAIL_BYTES);
        fseek($file, $tailStart);
        $tail = (string) fread($file, self::OGG_TAIL_BYTES);
        for ($position = strrpos($tail, 'OggS'); false !== $position; $position = $position > 0 ? strrpos(substr($tail, 0, $position), 'OggS') : false) {
            $granule = self::unpackInt('P', substr($tail, $position + 6, 8), 8);
            // -1 : page sans fin de paquet, la position se lit sur une page précédente
            if ($granule > 0) {
                return new SourceAudio(AudioFormat::Ogg, max(0, $granule - $preSkip) / $sampleRate);
            }
        }

        return null;
    }

    /**
     * @param resource $file
     */
    private function mp4($file, int $size): ?SourceAudio
    {
        $position = 0;
        while ($position + 8 <= $size) {
            fseek($file, $position);
            $header = (string) fread($file, 16);
            [$boxSize, $headerSize] = $this->boxSize($header, $size - $position);
            if ($boxSize < $headerSize) {
                return null;
            }
            if ('moov' === substr($header, 4, 4)) {
                if ($boxSize > self::MP4_MAX_MOOV_BYTES) {
                    return null;
                }
                fseek($file, $position + $headerSize);
                $length = $boxSize - $headerSize;

                return $length > 0 ? $this->moov((string) fread($file, $length)) : null;
            }
            $position += $boxSize;
        }

        return null;
    }

    private function moov(string $moov): ?SourceAudio
    {
        $duration = null;
        $handlers = [];
        foreach ($this->boxes($moov) as [$type, $payload]) {
            if ('mvhd' === $type) {
                $duration = $this->mvhdDuration($payload);
            }
            if ('trak' === $type) {
                $handlers[] = $this->handlerType($payload);
            }
        }
        if (null === $duration || !\in_array('soun', $handlers, true) || \in_array('vide', $handlers, true)) {
            return null;
        }

        return new SourceAudio(AudioFormat::M4a, $duration);
    }

    private function mvhdDuration(string $mvhd): ?float
    {
        if (\strlen($mvhd) < 32) {
            return null;
        }
        $values = 1 === \ord($mvhd[0])
            ? unpack('Ntimescale/Jduration', substr($mvhd, 20, 12))
            : unpack('Ntimescale/Nduration', substr($mvhd, 12, 8));
        if (false === $values || !\is_int($values['timescale']) || !\is_int($values['duration']) || $values['timescale'] < 1) {
            return null;
        }

        return $values['duration'] / $values['timescale'];
    }

    private function handlerType(string $trak): ?string
    {
        foreach ($this->boxes($trak) as [$type, $mdia]) {
            if ('mdia' !== $type) {
                continue;
            }
            foreach ($this->boxes($mdia) as [$childType, $hdlr]) {
                if ('hdlr' === $childType) {
                    return substr($hdlr, 8, 4);
                }
            }
        }

        return null;
    }

    /**
     * Boîtes filles d'un contenu déjà lu.
     *
     * @return list<array{string, string}> type, contenu
     */
    private function boxes(string $data): array
    {
        $boxes = [];
        $position = 0;
        $length = \strlen($data);
        while ($position + 8 <= $length) {
            [$boxSize, $headerSize] = $this->boxSize(substr($data, $position, 16), $length - $position);
            if ($boxSize < $headerSize || $position + $boxSize > $length) {
                break;
            }
            $boxes[] = [substr($data, $position + 4, 4), substr($data, $position + $headerSize, $boxSize - $headerSize)];
            $position += $boxSize;
        }

        return $boxes;
    }

    /**
     * Taille d'une boîte et de son en-tête : 1 annonce une taille sur 64 bits, 0 une boîte qui va jusqu'à la fin.
     *
     * @return array{int, int}
     */
    private function boxSize(string $header, int $remaining): array
    {
        if (\strlen($header) < 8) {
            return [0, 8];
        }
        $size = self::uint32(substr($header, 0, 4));
        if (1 === $size && \strlen($header) >= 16) {
            return [self::unpackInt('J', substr($header, 8, 8), 8), 16];
        }

        return [0 === $size ? $remaining : $size, 8];
    }

    private static function uint32(string $bytes): int
    {
        return self::unpackInt('N', $bytes, 4);
    }

    /**
     * Entier lu avec un format de unpack ; 0 si les octets manquent.
     */
    private static function unpackInt(string $format, string $bytes, int $length): int
    {
        $value = \strlen($bytes) === $length ? unpack($format, $bytes) : false;

        return false !== $value && \is_int($value[1] ?? null) ? $value[1] : 0;
    }
}
