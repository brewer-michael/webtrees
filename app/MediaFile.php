<?php

/**
 * webtrees: online genealogy
 * Copyright (C) 2019 webtrees development team
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Fisharebest\Webtrees;

use League\Flysystem\Adapter\Local;
use League\Flysystem\FileNotFoundException;
use League\Flysystem\Filesystem;
use League\Glide\Signatures\SignatureFactory;

use function getimagesize;
use function intdiv;
use function pathinfo;
use function strtolower;

use const PATHINFO_EXTENSION;

/**
 * A GEDCOM media file.  A media object can contain many media files,
 * such as scans of both sides of a document, the transcript of an audio
 * recording, etc.
 */
class MediaFile
{
    private const MIME_TYPES = [
        'bmp'  => 'image/bmp',
        'doc'  => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'ged'  => 'text/x-gedcom',
        'gif'  => 'image/gif',
        'html' => 'text/html',
        'htm'  => 'text/html',
        'jpeg' => 'image/jpeg',
        'jpg'  => 'image/jpeg',
        'mov'  => 'video/quicktime',
        'mp3'  => 'audio/mpeg',
        'mp4'  => 'video/mp4',
        'ogv'  => 'video/ogg',
        'pdf'  => 'application/pdf',
        'png'  => 'image/png',
        'rar'  => 'application/x-rar-compressed',
        'swf'  => 'application/x-shockwave-flash',
        'svg'  => 'image/svg',
        'tiff' => 'image/tiff',
        'tif'  => 'image/tiff',
        'xls'  => 'application/vnd-ms-excel',
        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'wmv'  => 'video/x-ms-wmv',
        'zip'  => 'application/zip',
    ];

    private const SUPPORTED_IMAGE_MIME_TYPES = [
        'image/gif',
        'image/jpeg',
        'image/png',
    ];

    /** @var string The filename */
    private $multimedia_file_refn = '';

    /** @var string The file extension; jpeg, txt, mp4, etc. */
    private $multimedia_format = '';

    /** @var string The type of document; newspaper, microfiche, etc. */
    private $source_media_type = '';
    /** @var string The filename */

    /** @var string The name of the document */
    private $descriptive_title = '';

    /** @var Media $media The media object to which this file belongs */
    private $media;

    /** @var string */
    private $fact_id;

    /**
     * Create a MediaFile from raw GEDCOM data.
     *
     * @param string $gedcom
     * @param Media  $media
     */
    public function __construct($gedcom, Media $media)
    {
        $this->media   = $media;
        $this->fact_id = md5($gedcom);

        if (preg_match('/^\d FILE (.+)/m', $gedcom, $match)) {
            $this->multimedia_file_refn = $match[1];
            $this->multimedia_format    = pathinfo($match[1], PATHINFO_EXTENSION);
        }

        if (preg_match('/^\d FORM (.+)/m', $gedcom, $match)) {
            $this->multimedia_format = $match[1];
        }

        if (preg_match('/^\d TYPE (.+)/m', $gedcom, $match)) {
            $this->source_media_type = $match[1];
        }

        if (preg_match('/^\d TITL (.+)/m', $gedcom, $match)) {
            $this->descriptive_title = $match[1];
        }
    }

    /**
     * Get the format.
     *
     * @return string
     */
    public function format(): string
    {
        return $this->multimedia_format;
    }

    /**
     * Get the type.
     *
     * @return string
     */
    public function type(): string
    {
        return $this->source_media_type;
    }

    /**
     * Get the title.
     *
     * @return string
     */
    public function title(): string
    {
        return $this->descriptive_title;
    }

    /**
     * Get the fact ID.
     *
     * @return string
     */
    public function factId(): string
    {
        return $this->fact_id;
    }

    /**
     * @return bool
     */
    public function isPendingAddition(): bool
    {
        foreach ($this->media->facts() as $fact) {
            if ($fact->id() === $this->fact_id) {
                return $fact->isPendingAddition();
            }
        }

        return false;
    }

    /**
     * @return bool
     */
    public function isPendingDeletion(): bool
    {
        foreach ($this->media->facts() as $fact) {
            if ($fact->id() === $this->fact_id) {
                return $fact->isPendingDeletion();
            }
        }

        return false;
    }

    /**
     * Display an image-thumbnail or a media-icon, and add markup for image viewers such as colorbox.
     *
     * @param int      $width            Pixels
     * @param int      $height           Pixels
     * @param string   $fit              "crop" or "contain"
     * @param string[] $image_attributes Additional HTML attributes
     *
     * @return string
     */
    public function displayImage($width, $height, $fit, $image_attributes = []): string
    {
        if ($this->isExternal()) {
            $src    = $this->multimedia_file_refn;
            $srcset = [];
        } else {
            // Generate multiple images for displays with higher pixel densities.
            $src    = $this->imageUrl($width, $height, $fit);
            $srcset = [];
            foreach ([2, 3, 4] as $x) {
                $srcset[] = $this->imageUrl($width * $x, $height * $x, $fit) . ' ' . $x . 'x';
            }
        }

        if ($this->isImage()) {
            $image = '<img ' . Html::attributes($image_attributes + [
                        'dir'    => 'auto',
                        'src'    => $src,
                        'srcset' => implode(',', $srcset),
                        'alt'    => htmlspecialchars_decode(strip_tags($this->media->fullName())),
                    ]) . '>';

            $link_attributes = Html::attributes([
                'class'      => 'gallery',
                'type'       => $this->mimeType(),
                'href'       => $this->imageUrl(0, 0, 'contain'),
                'data-title' => htmlspecialchars_decode(strip_tags($this->media->fullName())),
            ]);
        } else {
            $image = view('icons/mime', ['type' => $this->mimeType()]);

            $link_attributes = Html::attributes([
                'type' => $this->mimeType(),
                'href' => $this->downloadUrl(),
            ]);
        }

        return '<a ' . $link_attributes . '>' . $image . '</a>';
    }

    /**
     * Is the media file actually a URL?
     */
    public function isExternal(): bool
    {
        return strpos($this->multimedia_file_refn, '://') !== false;
    }

    /**
     * Generate a URL for an image.
     *
     * @param int    $width  Maximum width in pixels
     * @param int    $height Maximum height in pixels
     * @param string $fit    "crop" or "contain"
     *
     * @return string
     */
    public function imageUrl($width, $height, $fit): string
    {
        // Sign the URL, to protect against mass-resize attacks.
        $glide_key = Site::getPreference('glide-key');
        if ($glide_key === '') {
            $glide_key = bin2hex(random_bytes(128));
            Site::setPreference('glide-key', $glide_key);
        }

        if (Auth::accessLevel($this->media->tree()) > $this->media->tree()->getPreference('SHOW_NO_WATERMARK')) {
            $mark = 'watermark.png';
        } else {
            $mark = '';
        }

        $params = [
            'xref'      => $this->media->xref(),
            'tree'      => $this->media->tree()->name(),
            'fact_id'   => $this->fact_id,
            'w'         => $width,
            'h'         => $height,
            'fit'       => $fit,
            'mark'      => $mark,
            'markh'     => '100h',
            'markw'     => '100w',
            'markalpha' => 25,
            'or'        => 0,
        ];

        $signature = SignatureFactory::create($glide_key)->generateSignature('', $params);

        $params = ['route' => '/media-thumbnail', 's' => $signature] + $params;

        return route('media-thumbnail', $params);
    }

    /**
     * Is the media file an image?
     */
    public function isImage(): bool
    {
        return in_array($this->mimeType(), self::SUPPORTED_IMAGE_MIME_TYPES, true);
    }

    /**
     * What is the mime-type of this object?
     * For simplicity and efficiency, use the extension, rather than the contents.
     *
     * @return string
     */
    public function mimeType(): string
    {
        $extension = strtolower(pathinfo($this->multimedia_file_refn, PATHINFO_EXTENSION));

        return self::MIME_TYPES[$extension] ?? 'application/octet-stream';
    }

    /**
     * Generate a URL to download a non-image media file.
     *
     * @return string
     */
    public function downloadUrl(): string
    {
        return route('media-download', [
            'xref'    => $this->media->xref(),
            'tree'    => $this->media->tree()->name(),
            'fact_id' => $this->fact_id,
        ]);
    }

    /**
     * A list of image attributes
     *
     * @return string[]
     */
    public function attributes(): array
    {
        $attributes = [];

        if (!$this->isExternal() || $this->fileExists()) {
            try {
                $bytes                       = $this->media()->tree()->mediaFilesystem()->getSize($this->filename());
                $kb                          = intdiv($bytes + 1023, 1024);
                $attributes['__FILE_SIZE__'] = I18N::translate('%s KB', I18N::number($kb));
            } catch (FileNotFoundException $ex) {
                // External/missing files have no size.
            }

            // Note: getAdapter() is defined on Filesystem, but not on FilesystemInterface.
            $filesystem = $this->media()->tree()->mediaFilesystem();
            if ($filesystem instanceof Filesystem) {
                $adapter = $filesystem->getAdapter();
                // Only works for local filesystems.
                if ($adapter instanceof Local) {
                    $file = $adapter->applyPathPrefix($this->filename());
                    [$width, $height] = getimagesize($file);
                    $attributes['__IMAGE_SIZE__'] = I18N::translate('%1$s × %2$s pixels', I18N::number($width), I18N::number($height));
                }
            }
        }

        return $attributes;
    }

    /**
     * Read the contents of a media file.
     *
     * @return string
     */
    public function fileContents(): string
    {
        return $this->media->tree()->mediaFilesystem()->read($this->multimedia_file_refn);
    }

    /**
     * Check if the file exists on this server
     *
     * @return bool
     */
    public function fileExists(): bool
    {
        return $this->media->tree()->mediaFilesystem()->has($this->multimedia_file_refn);
    }

    /**
     * @return Media
     */
    public function media(): Media
    {
        return $this->media;
    }

    /**
     * Get the filename.
     *
     * @return string
     */
    public function filename(): string
    {
        return $this->multimedia_file_refn;
    }

    /**
     * What file extension is used by this file?
     *
     * @return string
     */
    public function extension(): string
    {
        return pathinfo($this->multimedia_file_refn, PATHINFO_EXTENSION);
    }
}
