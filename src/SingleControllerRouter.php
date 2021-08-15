<?php
namespace Junpbelmondo\SimpleRouter;

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
     * @see \Junpbelmondo\SimpleRouter\AbstractRouter::parse()
     */
    protected function parse(Url $url): array
    {
        $url_paths = $url->getPaths();

        // コントローラはルートコントローラのみを使用する。
        $this->setControllerName($this->root_controller_name);

        // パスが入っている場合、最初のパスをアクションとする。そうでない場合デフォルトのアクションを使用する。
        $action = $this->default_action;
        if(!empty($url_paths)) {
            $action = array_shift($url_paths);
        }
        $this->setAction($action);

        return $url_paths;
    }
}
