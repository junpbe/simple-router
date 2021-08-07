<?php
namespace Junpbelmondo\SimpleRouter\Routing;

/**
 * シングルコントローラルータ。
 *
 * path: /app_base_path/to/action/param1/param2
 * to be called: \AppNameSpaceHoge\AppNameSpaceMoge\Controllers\RootController::action($param1, $param2)
 */
class SingleControllerRouter extends AbstractRouter
{
    /**
     * {@inheritDoc}
     * @see \Junpbelmondo\SimpleRouter\Routing\AbstractRouter::parse()
     */
    protected function parse(Url $url): array
    {
        $url_paths = $url->getPaths();

        // コントローラはルートコントローラのみを使用する。
        $this->controller_name = $this->root_controller_name;

        // パスが入っている場合、最初のパスをアクションとする。そうでない場合デフォルトのアクションを使用する。
        $action = $this->default_action;
        if(!empty($url_paths)) {
            $action = array_shift($url_paths);
        }
        $this->action = $action;

        return $url_paths;
    }
}
