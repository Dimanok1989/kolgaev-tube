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
    protected $uuid;

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
     * Инициализация сервиса
     * 
     * @param string $url
     * @return void
     */
    public function __construct(
        private ?string $url = null
    ) {

        throw_if(empty($url), EmptyUrlException::class, 'Необходимо указать ссылку');

        if (empty($this->phyton = exec('which python'))) {
            $this->phyton = exec('which python3');
        }

        $this->dir = base_path('pytube');
        $this->streams = collect();

        $this->uuid = Str::orderedUuid()->toString();

        $storage = Storage::disk('public');
        $this->path = $storage->path($this->uuid);
        $storage->makeDirectory($this->uuid);

        $this->setMeta();
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
            throw new SetStreamsException("Ошибка получения мета данных");
        }

        $this->title = $output[0] ?? null;
        $this->thumbnailUrl = $output[1] ?? null;

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
        return implode(" ", [
            $this->phyton,
            $this->dir . DIRECTORY_SEPARATOR . "download.py",
            $this->url,
            $this->path,
            $itag
        ]);
    }

    /**
     * Получение идентификаторов скачивания медиа данных
     * 
     * @return array
     */
    private function setItags()
    {
        $maxVideo = $this->streams->max('res_int');

        $itags['video'] = $this->findItag($this->streams, ...[
            'type' => 'video',
            'mime_type' => 'video/mp4',
            'res_int' => $maxVideo,
        ]);

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

        return $this->itags = $itags;
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
     * Скачивание файла
     * 
     * @param string $itag
     * @return array
     */
    private function download(string $itag)
    {
        $response = [];

        $process = Process::run(
            $this->downloadCommand($itag),
            function (string $type, string $output) use (&$response) {
                if ($type == "out") {
                    $response[] = $output;
                }
            }
        );

        if ($process->failed()) {
            throw new Exception("Ошибка скачиваания файла");
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

    /**
     * Формирует путь
     * 
     * @param null|string $path
     * @return string
     */
    public function path(?string $path = null)
    {
        return $this->uuid
            . (!empty($path) ? DIRECTORY_SEPARATOR . $path : "");
    }
}
