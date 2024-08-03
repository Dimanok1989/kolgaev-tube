<?php

namespace App\Services;

use App\Exceptions\Pytube\EmptyUrlException;
use App\Exceptions\Pytube\SetStreamsException;
use Exception;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Pytube
{
    /**
     * Путь до исполнительного файла phyton
     * 
     * @var string
     */
    protected $phyton;

    /**
     * Путь до каталога со скриптами
     * 
     * @var string
     */
    protected $dir;

    /**
     * Идентификатор операции
     * 
     * @var string
     */
    public $uuid;

    /**
     * Наименование видео
     * 
     * @var null|string
     */
    protected $title;

    /**
     * Ссылка на превьюшку
     * 
     * @var null|string
     */
    protected $thumbnailUrl;

    /**
     * Доступные потоковые данные для скачивания
     * 
     * @var \Illuminate\Support\Collection
     */
    protected $streams;

    /**
     * Идентификаторы потоков для скачивания в максимальном качестве
     * 
     * @var array
     */
    protected $itags;

    /**
     * Путь до каталога хранения файлов
     * 
     * @var string
     */
    protected $path;

    /**
     * Мета данные видео
     * 
     * @var array
     */
    protected $meta = [];

    /**
     * Идентификатор видео
     * 
     * @var string
     */
    protected $videoId;

    /**
     * Инициализация сервиса
     * 
     * @param string $url
     * @return void
     */
    public function __construct(
        private ?string $url = null,
        ?string $uuid = null
    ) {

        throw_if(empty($url), EmptyUrlException::class, 'Необходимо указать ссылку');

        if (empty($this->phyton = exec('which python'))) {
            $this->phyton = exec('which python3');
        }

        $this->videoId = $this->parseVideoId();

        $this->dir = base_path('pytube');
        $this->streams = collect();

        $this->uuid = $uuid ?: Str::orderedUuid()->toString();

        $storage = Storage::disk('local');

        if (!$storage->exists('youtube')) {
            $storage->makeDirectory('youtube');
            $this->setPermit($storage->path('youtube'));
        }

        $this->path = $storage->path('youtube/' . $this->uuid);
        $storage->makeDirectory('youtube/' . $this->uuid);

        $this->setPermit($this->path);

        $this->setMeta();
    }

    /**
     * Устаналиваает права на файл
     * 
     * @param string $path
     * @return void
     */
    public static function setPermit(string $path)
    {
        chown($path, env('TUBE_OWNER_USER', 'www-data'));
        chgrp($path, env('TUBE_OWNER_GROUP', 'www-data'));
        chmod($path, 755);
    }

    /**
     * Поиск идентиифкатор видео
     * 
     * @return string
     */
    public function parseVideoId()
    {
        $parseUrl = parse_url($this->url);
        $host = $parseUrl['host'] ?? "";

        if ($host == "youtu.be") {
            $videoId = pathinfo($parseUrl['path'] ?? "", PATHINFO_BASENAME);
        } else if (Str::position($host, "youtube.com") !== false) {
            parse_str($parseUrl['query'] ?? "", $query);
            $videoId = $query['v'] ?? null;
        }

        abort_if(empty($videoId), Exception::class, "Не найден идентификатор видео");

        return $this->videoId = ($videoId ?? null);
    }

    /**
     * Возвращает идентификатор видео
     * 
     * @return string
     */
    public function getVideoId()
    {
        return $this->videoId;
    }

    /**
     * Получение мета данных
     * 
     * @return void
     * 
     * @throws \App\Services\Pytube\Exceptions\SetStreamsException
     */
    private function setMeta()
    {
        $result = Process::run($this->metaCommand());
        $output = explode("\n", $result->output());

        if ($result->failed()) {
            \Log::error('set meta error:', $output);
            throw new SetStreamsException("Ошибка получения мета данных");
        }

        $this->title = $output[0] ?? null;
        $this->meta['title'] = $this->title;

        $this->thumbnailUrl = $output[1] ?? null;
        $this->meta['thumbnail_url'] = $this->thumbnailUrl;

        $this->meta['channel_id'] = $output[3] ?? null;
        $this->meta['length'] = $output[4] ?? null;
        $this->meta['publish_date'] = $output[5] ?? null;
        $this->meta['description'] = collect($output)->splice(6)->join("\n");

        $streams = Str::replaceFirst('[', '', Str::replaceLast(']', '',  $output[2] ?? ""));

        $this->streams = collect(explode(",", $streams))
            ->map(fn ($item) => Str::squish($item))
            ->map(fn ($item) => Str::replace("Stream: ", "", $item))
            ->map(function ($item) {
                preg_match_all('/(\w+)="([^"]+)"/', $item, $matches);
                return array_combine($matches[1], $matches[2]);
            })
            ->map(function ($item) {

                if ($item['type'] == "video") {
                    $item['res_int'] = (int) preg_replace('/[^0-9]/', '', $item['res'] ?? '');
                }

                if ($item['type'] == "audio") {
                    $item['abr_int'] = (int) preg_replace('/[^0-9]/', '', $item['abr'] ?? '');
                }

                return $item;
            });

        $this->meta['streams'] =  $this->streams;

        $this->setItags();
    }

    /**
     * Формирует команду получения мета данных
     * 
     * @return string
     */
    private function metaCommand()
    {
        return implode(" ", [
            $this->phyton,
            $this->dir . DIRECTORY_SEPARATOR . "meta.py",
            $this->url
        ]);
    }

    /**
     * Формирует команду скачивания файла
     * 
     * @param string $itag
     * @return string
     */
    private function downloadCommand(string $itag = "")
    {
        $parts = [
            $this->phyton,
            $this->dir . DIRECTORY_SEPARATOR . "download.py",
            '"' . $this->url . '"',
            '"' . $this->path . '"',
            $itag,
        ];

        $filename = Str::random(50);

        if ($mimeType = collect($this->streams)->firstWhere('itag', $itag)['mime_type'] ?? null) {
            if ($extension = explode("/", $mimeType)[1] ?? null) {
                $filename = Str::slug($this->title) . "." . $extension;
            }
        }

        $parts[] = $filename;

        return implode(" ", $parts);
    }

    /**
     * Получение идентификаторов скачивания медиа данных
     * 
     * @return array
     */
    private function setItags()
    {
        $itags['video'] = $this->findItag($this->streams, ...[
            'type' => 'video',
            'mime_type' => 'video/mp4',
            'res' => "1080p",
        ]);

        $maxVideo = $this->streams->max('res_int');

        if (empty($itags['video'])) {
            $itags['video'] = $this->findItag($this->streams, ...[
                'type' => 'video',
                'mime_type' => 'video/mp4',
                'res_int' => $maxVideo,
            ]);
        }

        if (empty($itags['video'])) {
            $itags['video'] = $this->findItag($this->streams, ...[
                'type' => 'video',
                'res_int' => $maxVideo,
            ]);
        }

        $maxAudio = $this->streams->max('abr_int');

        $itags['audio'] = $this->findItag($this->streams, ...[
            'type' => 'audio',
            'abr_int' => $maxAudio,
        ]);

        $this->itags = $itags;
        $this->meta['itags'] = $this->itags;

        return $this->itags;
    }

    /**
     * Поиск идентификатора файла по атрибуиам
     * 
     * @param \Illuminate\Support\Collection $streams
     * @param array $attributes
     * @return string|null
     */
    private function findItag($streams, ...$attributes)
    {
        foreach ($attributes as $key => $value) {
            $streams = $streams->where($key, $value);
        }

        return $streams->first()['itag'] ?? null;
    }

    /**
     * Формирует путь относительно каталога процесса
     * 
     * @param null|string $path
     * @return string
     */
    public function path(?string $path = null)
    {
        return collect(['youtube', $this->uuid, $path])
            ->filter()
            ->join(DIRECTORY_SEPARATOR);
    }

    /**
     * Скачивание файла
     * 
     * @param string $itag
     * @return array
     */
    private function download(string $itag)
    {
        $response = [];

        $process = Process::timeout(3600)
            ->run(
                $this->downloadCommand($itag),
                function (string $type, string $output) use (&$response) {
                    if ($type == "out") {
                        $response[] = $output;
                    }
                }
            );

        if ($process->failed()) {
            throw new Exception("Ошибка скачивания файла");
        }

        return $response;
    }

    /**
     * Скачивание видео
     * 
     * @return array
     */
    public function downloadVideo()
    {
        return $this->download($this->itags['video']);
    }

    /**
     * Скачивание аудиодорожки
     * 
     * @return array
     */
    public function downloadAudio()
    {
        return $this->download($this->itags['audio']);
    }

    /**
     * Возврашает мета данные
     * 
     * @param null|string $key
     * @return mixed
     */
    public function meta(?string $key = null)
    {
        return !empty($key)
            ? ($this->meta[$key] ?? null)
            : $this->meta;
    }

    /**
     * Наименование видео
     * 
     * @return null|string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Ссылка на превью
     * 
     * @return null|string
     */
    public function getThumbnailUrl()
    {
        return $this->thumbnailUrl;
    }
}
