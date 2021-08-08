<?php
namespace Junpbelmondo\SimpleRouter\Routing;

use Junpbelmondo\SimpleRouter\Exceptions\NotFoundException;
use Throwable;

/**
 * ルータ。
 */
abstract class AbstractRouter
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

    /** @var string コントローラ名 */
    private $controller_name = '';

    /** @var string アクション */
    private $action = '';

    /**
     * urlを変換して情報をセット。
     *
     * @param \Junpbelmondo\SimpleRouter\Routing\Url $url URLパスの配列
     * @return string[] アクションを呼び出すときのパラメータ
     */
    abstract protected function parse(Url $url): array;

    /**
     * コンストラクタ。
     *
     * @param string $app_name_space アプリケーションの名前空間
     * @param string $app_base_path アプリケーション基底パス
     * @param array $options 設定
     */
    public function __construct(string $app_name_space, string $app_base_path, array $options = [])
    {
        $this->app_name_space = $app_name_space;
        $this->app_base_path = $app_base_path;
        $this->root_controller_name = $options['root_controller_name'] ?? $this->root_controller_name;
        $this->default_action = $options['default_action'] ?? $this->default_action;
        $this->controllers_name_space = $options['controllers_name_space'] ?? $this->controllers_name_space;
        $this->controllers_suffix = $options['controllers_suffix'] ?? $this->controllers_suffix;
    }

    /**
     * アプリケーションの名前空間取得。
     *
     * @return string アプリケーションの名前空間
     */
    public function getAppNameSpace(): string
    {
        return $this->app_name_space;
    }

    /**
     * アプリケーション基底パス取得。
     *
     * @return string アプリケーション基底パス
     */
    public function getAppBasePath(): string
    {
        return $this->app_base_path;
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
     * コントローラクラス名（完全修飾名）取得。
     *
     * @return string コントローラクラス名（完全修飾名）
     */
    public function getControllerFQCN(): string
    {
        return $this->buildControllerFQCN($this->controller_name);
    }

    /**
     * アクション取得。
     *
     * @return string アクション
     */
    public function getAction(): string
    {
        return $this->action;
    }

    /**
     * ディスパッチ。
     *
     * @return string レスポンス
     * @throws Throwable デバッグモードの場合で予期せぬエラーが発生した場合
     */
    public function dispatch(): string
    {
        // リクエストURLを分解して、情報をセット
        $url = new Url($_SERVER['REQUEST_URI'], $this->app_base_path);
        $params = $this->parse($url);

        try {
            // コントローラ生成
            $controller = $this->createController($this->controller_name);

            // アクション呼び出し
            return $this->invoke($controller, $this->action, $params);
        } catch(NotFoundException $e) {
            if(defined('DEBUG') && DEBUG) {
                throw $e;
            }
            // コントローラは存在するがアクションメソッドがなかった場合は、そのコントローラの404メソッドを呼び出す
            if(isset($controller)) {
                try {
                    return $this->invoke($controller, 'e404');
                } catch(NotFoundException $ignore) {
                }
            }
            // コントローラが存在しなかった場合、コントローラに404メソッドが無かった場合は、ルートコントローラの404メソッドを呼び出す
            try {
                return $this->invoke($this->createController($this->root_controller_name), 'e404');
            } catch(NotFoundException $e) {
                // ルートコントローラに404メソッドがない場合、404ヘッダを出力して終了
                header("HTTP/1.1 404 Not Found");
                return '';
            }
        } catch(Throwable $t) {
            if(defined('DEBUG') && DEBUG) {
                throw $t;
            }
            // コントローラは存在するがアクションメソッドがなかった場合は、そのコントローラの500メソッドを呼び出す
            if(isset($controller)) {
                try {
                    return $this->invoke($controller, 'e500');
                } catch(NotFoundException $ignore) {
                }
            }
            // コントローラが存在しなかった場合、コントローラに500メソッドが無かった場合は、ルートコントローラの500メソッドを呼び出す
            try {
                return $this->invoke($this->createController($this->root_controller_name), 'e500');
            } catch(NotFoundException $e) {
                // ルートコントローラに500メソッドがない場合、500ヘッダを出力して終了
                header('HTTP/1.1 500 Internal Server Error');
                return '';
            }
        }
    }

    /**
     * コントローラ名設定。
     *
     * @param string $controller_name コントローラ名
     */
    protected function setControllerName(string $controller_name): void
    {
        $this->controller_name = $controller_name;
    }

    /**
     * アクション設定。
     *
     * @param string $action アクション
     */
    protected function setAction(string $action): void
    {
        $this->action = $action;
    }

    /**
     * コントローラ名からクラスの完全修飾名を作る。
     *
     * @param string $controller_name コントローラ名
     * @return string コントローラクラスの完全修飾名
     */
    protected function buildControllerFQCN(string $controller_name): string
    {
        return "{$this->app_name_space}{$this->controllers_name_space}\\{$controller_name}{$this->controllers_suffix}";
    }

    /**
     * コントローラ生成。
     *
     * @param string $controller_name コントローラ名
     * @throws NotFoundException コントローラが存在しなかった場合
     */
    protected function createController(string $controller_name): object
    {
        $fqcn = $this->buildControllerFQCN($controller_name);

        // コントローラが存在しない場合エラー（404にする）
        if(!class_exists($fqcn)) {
            throw new NotFoundException("コントローラが存在しなかった。[controller_name={$fqcn}]");
        }

        return new $fqcn();
    }

    /**
     * アクション実行。
     *
     * @param object $controller コントローラ
     * @param string $action アクション
     * @param array $params パラメータ
     * @return string レスポンス
     * @throws NotFoundException メソッドがなかった場合
     */
    protected function invoke(object $controller, string $action, array $params = []): string
    {
        // コントローラに呼び出すメソッドがない場合エラー（404にする）
        if(!method_exists($controller, $action)) {
            throw new NotFoundException("メソッドがなかった。[controller_name=" . get_class($controller) . ", action={$action}]");
        }

        return $controller->$action(...$params);
    }
}
