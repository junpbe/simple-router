<?php
namespace Junpbelmondo\SimpleRouter\Routing;

/**
 * URL。
 */
class Url
{
    /** @var string URI */
    private $uri = '';

    /** @var string アプリケーション基底パス */
    private $app_base_path = '';

    /**
     * コンストラクタ。
     *
     * @param string $uri URI
     * @param string $app_base_path アプリケーション基底パス
     */
    public function __construct(string $uri, string $app_base_path = '')
    {
        $this->uri = $uri;
        $this->app_base_path = $app_base_path;
    }

    /**
     * URL取得（アプリケーション相対）。
     *
     * @return string URI
     */
    public function getUrl(): string
    {
        return '/' . implode('/', $this->getPaths());
    }

    /**
     * URL取得（完全）。
     *
     * @return string URI
     */
    public function getFullUrl(): string
    {
        return $this->uri;
    }

    /**
     * URLのパス配列を取得（アプリケーション相対）。
     *
     * @return string[] URLのパス配列
     */
    public function getPaths(): array
    {
        $url_paths = $this->getFullPaths();

        // URLのパスからアプリケーション基底パスを除く
        foreach($this->parsePaths($this->app_base_path) as $idx => $path) {
            if(!isset($url_paths[$idx])) {
                break;
            }

            if($url_paths[$idx] === $path) {
                unset($url_paths[$idx]);
            }
        }

        return array_values($url_paths);
    }

    /**
     * URLのパス配列を取得（完全）。
     *
     * @return string[] URLのパス配列
     */
    public function getFullPaths(): array
    {
        // URLのパスの配列を取得
        return $this->parsePaths($this->uri);
    }

    /**
     * パス分解。
     *
     * @param string $url 分解するurl
     * @return string[] 分解したパス
     */
    protected function parsePaths($url): array
    {
        return array_values(array_filter(explode('/', $url), function ($v) {
            return (trim($v) !== '');
        }));
    }
}
