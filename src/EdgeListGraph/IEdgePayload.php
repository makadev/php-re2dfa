<?php


namespace makadev\RE2DFA\EdgeListGraph;

/**
 * Interface IEdgePayload
 *
 * @package makadev\RE2DFA\EdgeListGraph
 */
interface IEdgePayload {

    public function isColliding(IEdgePayload $other): bool;

    public function copy(): IEdgePayload;
}
