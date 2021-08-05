<?php
namespace Junpbelmondo\SimpleRouter\Routing;

use Junpbelmondo\SimpleRouter\Exceptions\NotFoundException;
use Throwable;

/**
 * ルータ。
 */
class Router
{
    /** @var string ルートコントローラ名 */
    protected $root_controller_name = 'Root';

    /** @var string デフォルトアクション */
    protected $default_action = 'index';

    /** @var string コントローラの名前空間 */
    protected $controllers_name_space = '\\Controllers';

    /** @var string コントローラクラスの接尾辞 */
    protected $controllers_suffix = 'Controller';

    /** @var string アプリケーションの名前空間 */
    private $app_name_space = '';

    /** @var string アプリケーション基底パス */
    private $app_base_path = '';

    /** @var bool シングルコントローラモード */
    private $is_single_controller_mode = true;

    /** @var string コントローラ名（完全修飾クラス名） */
    private $controller_name = '';

    /** @var object コントローラインスタンス */
    private $controller = null;

    /** @var string アクション */
    private $action = '';

    /**
     * コンストラクタ。
     *
     * @param string $app_name_space アプリケーションの名前空間
     * @param string $app_base_path アプリケーション基底パス
     * @param bool $is_single_controller_mode シングルコントローラモードの場合true、そうでない場合false
     */
    public function __construct(string $app_name_space, string $app_base_path, bool $is_single_controller_mode = true)
    {
        $this->app_name_space = $app_name_space;
        $this->app_base_path = $app_base_path;
        $this->is_single_controller_mode = $is_single_controller_mode;
    }

    /**
     * ルートコントローラ名設定。
     *
     * @param string $root_controller_name ルートコントローラ名
     */
    public function setRootControllerName($root_controller_name)
    {
        $this->root_controller_name = $root_controller_name;
    }

    /**
     * デフォルトアクション設定。
     *
     * @param string $default_action デフォルトアクション
     */
    public function setDefaultAction($default_action)
    {
        $this->default_action = $default_action;
    }

    /**
     * コントローラの名前空間設定。
     *
     * @param string $controllers_name_space コントローラの名前空間
     */
    public function setControllersNameSpace($controllers_name_space)
    {
        $this->controllers_name_space = $controllers_name_space;
    }

    /**
     * コントローラクラスの接尾辞設定。
     *
     * @param string $controllers_suffix コントローラクラスの接尾辞
     */
    public function setControllersSuffix($controllers_suffix)
    {
        $this->controllers_suffix = $controllers_suffix;
    }

    /**
     * アプリケーションの名前空間取得。
     *
     * @return string アプリケーションの名前空間
     */
    public function getAppNameSpace()
    {
        return $this->app_name_space;
    }

    /**
     * アプリケーション基底パス取得。
     *
     * @return string アプリケーション基底パス
     */
    public function getAppBasePath()
    {
        return $this->app_base_path;
    }

    /**
     * シングルコントローラモード。
     *
     * @return boolean シングルコントローラモードの場合true、そうでない場合false
     */
    public function isSinglecontrollerMode()
    {
        return $this->is_single_controller_mode;
    }

    /**
     * コントローラ名取得。
     *
     * @return string コントローラ名
     */
    public function getControllerName(): string
    {
        return $this->controller_name;
    }

    /**
     * コントローラ取得。
     *
     * @return object コントローラ
     */
    public function getController()
    {
        return $this->controller;
    }

    /**
     * アクション取得。
     *
     * @return string アクション
     */
    public function getAction()
    {
        return $this->action;
    }

    /**
     * ディスパッチ。
     */
    public function dispatch()
    {
        try {
            // リクエストURLを分解して、初期処理
            $url_paths = $this->getUrlPaths();
            $url_paths = $this->init($url_paths);
            $this->createController();

            // 残りのURLパスをパラメータとしてアクションを呼び出す
            $this->getController()->{$this->getAction()}(...$url_paths);
        } catch(NotFoundException $e) {
            //TODO　各コントローラの404、もしなければrootコントローラの404
            header("HTTP/1.1 404 Not Found");
            exit();
        } catch(Throwable $t) {
            if(defined('DEBUG') && DEBUG) {
                throw $t;
            }
            header('HTTP/1.1 500 Internal Server Error');
            exit();
        }
    }

    /**
     * リクエストされたパスの配列を取得。
     *
     * @return string[] パスの配列
     */
    protected function getUrlPaths(): array
    {
        // URLのパスの配列を取得
        $url_paths = $this->pathExplode($_SERVER['REQUEST_URI']);

        // URLのパスからアプリケーション基底パスを除く
        foreach($this->pathExplode($this->app_base_path) as $idx => $path) {
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
     * パス分解。
     *
     * @param string $path 分解するパス
     * @return string[] 分解したパス
     */
    protected function pathExplode($path): array
    {
        return array_values(array_filter(explode('/', $path), function ($v) {
            return (trim($v) !== '');
        }));
    }

    /**
     * 初期処理。
     *
     * @param string[] $url_paths URLパスの配列
     * @return string[] URLパスの配列
     */
    protected function init(array $url_paths): array
    {
        // シングルコントローラモードでなく、パスが入っている場合、最初のパスをコントローラ名とする。そうでない場合ルートコントローラを使用する。
        $controller_name = $this->root_controller_name;
        if(!$this->is_single_controller_mode && !empty($url_paths)) {
            $controller_name = array_shift($url_paths);
        }

        $this->controller_name = "{$this->app_name_space}\\{$this->controllers_name_space}\\{$controller_name}{$this->controllers_suffix}";

        // パスが入っている場合、最初のパスをアクションとする。そうでない場合デフォルトのアクションを使用する。
        $action = $this->default_action;
        if(!empty($url_paths)) {
            $action = array_shift($url_paths);
        }

        $this->action = $action;

        return $url_paths;
    }

    /**
     * コントローラ生成。
     *
     * @param string $action アクション
     * @return object 生成したコントローラ
     * @throws NotFoundException コントローラが存在しなかった場合
     */
    protected function createController(string $controller_name, string $action): object
    {
        // コントローラが存在しない場合エラー（404にする）
        if(class_exists($controller_name)) {
            throw new NotFoundException("コントローラが存在しなかった。[controller_name={$controller_name}]");
        }

        // コントローラ生成
        $controller = new $controller_name();

        // コントローラに呼び出すメソッドがない場合エラー（404にする）
        $clazz = new \ReflectionClass($controller);
        if(!$clazz->hasMethod($action)) {
            throw new NotFoundException("メソッドがなかった。[controller_name={$controller_name}, action={$action}]");
        }

        return $controller;
    }
}
