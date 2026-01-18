<?php
declare(strict_types=1);

namespace Pradzikowski\Framework\Action;

use Pradzikowski\Framework\Action\ActionInterface;
use Pradzikowski\Framework\Attribute\Middleware;
use Pradzikowski\Framework\Attribute\Route;
use Pradzikowski\Framework\Http\Request;
use Pradzikowski\Framework\Http\Middleware\Dummy;
use Pradzikowski\Framework\Action\HelloResponse;
use Pradzikowski\Framework\Action\HelloResponseFactory;

#[Route(path: '/hello', method: 'GET')]
#[Route(path: '/hello/:name', method: 'GET')]
#[Middleware(Dummy::class, ['header' => 'ok'])]
class GetHelloAction implements ActionInterface
{
    public function __invoke(Request $request): HelloResponse
    {
        $name = $request->get('name', 'world')
                |> (fn($str) => urldecode($str))
                |> (fn($str) => trim($str))
                |> (fn($str) => htmlspecialchars($str, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'));
        return new HelloResponseFactory()->create($name);
    }
}
