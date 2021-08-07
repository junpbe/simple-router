<?php
namespace Junpbelmondo\SimpleRouter\Routing;

/**
 * マルチコントローラルータ。
 *
 * path: /app_base_path/to/namedcontroller/action/param1/param2
 * to be called: \AppNameSpaceHoge\AppNameSpaceMoge\Controllers\NamedController::action($param1, $param2)
 */
class MultipleControllerRouter extends AbstractRouter
{
    /**
     * {@inheritDoc}
     * @see \Junpbelmondo\SimpleRouter\Routing\AbstractRouter::parse()
     */
    protected function parse(Url $url): array
    {
        $url_paths = $url->getPaths();

        // パスが入っている場合、最初のパスをコントローラ名とする
        $controller_name = $this->root_controller_name;
        if(!empty($url_paths)) {
            $controller_name = ucfirst(array_shift($url_paths));
        }
        $this->setControllerName($controller_name);

        // パスが入っている場合、最初のパスをアクションとする。そうでない場合デフォルトのアクションを使用する。
        $action = $this->default_action;
        if(!empty($url_paths)) {
            $action = array_shift($url_paths);
        }
        $this->setAction($action);

        return $url_paths;
    }
}
