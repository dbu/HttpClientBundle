<?php

declare(strict_types=1);

namespace Http\HttplugBundle\Collector;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DataCollector\DataCollector;

/**
 * The Collector holds profiled Stacks pushed by the StackPlugin. It also has a list of the configured clients.
 * This data is used to display the HTTPlug panel in the Symfony profiler.
 *
 * The collector is not designed for execution in a threaded application and does not support plugins that execute an
 * other request before the current one is sent by the client.
 *
 * @author Fabien Bourigault <bourigaultfabien@gmail.com>
 *
 * @internal
 */
final class Collector extends DataCollector
{
    private ?Stack $activeStack = null;

    public function __construct(private ?int $capturedBodyLength = null)
    {
        $this->reset();
    }

    public function __wakeup(): void
    {
        $this->capturedBodyLength = null;

        parent::__wakeup();
    }

    public function reset(): void
    {
        $this->data['stacks'] = [];
        $this->activeStack = null;
    }

    public function getName(): string
    {
        return 'httplug';
    }

    public function getCapturedBodyLength(): ?int
    {
        return $this->capturedBodyLength;
    }

    /**
     * Mark the stack as active. If a stack was already active, use it as parent for our stack.
     */
    public function activateStack(Stack $stack): void
    {
        if (null !== $this->activeStack) {
            $stack->setParent($this->activeStack);
        }

        $this->activeStack = $stack;
    }

    /**
     * Mark the stack as inactive.
     */
    public function deactivateStack(Stack $stack): void
    {
        $this->activeStack = $stack->getParent();
    }

    public function getActiveStack(): ?Stack
    {
        return $this->activeStack;
    }

    public function addStack(Stack $stack): void
    {
        $this->data['stacks'][] = $stack;
    }

    /**
     * @return Stack[]
     */
    public function getChildrenStacks(Stack $parent): array
    {
        return array_filter($this->data['stacks'], static fn (Stack $stack) => $stack->getParent() === $parent);
    }

    /**
     * @return Stack[]
     */
    public function getStacks(): array
    {
        return $this->data['stacks'];
    }

    /**
     * @return Stack[]
     */
    public function getSuccessfulStacks(): array
    {
        return array_filter($this->data['stacks'], static fn (Stack $stack) => !$stack->isFailed());
    }

    /**
     * @return Stack[]
     */
    public function getFailedStacks(): array
    {
        return array_filter($this->data['stacks'], static fn (Stack $stack) => $stack->isFailed());
    }

    /**
     * @return string[]
     */
    public function getClients(): array
    {
        $stacks = array_filter($this->data['stacks'], static fn (Stack $stack) => null === $stack->getParent());

        return array_unique(array_map(static fn (Stack $stack) => $stack->getClient(), $stacks));
    }

    /**
     * @return Stack[]
     */
    public function getClientRootStacks(string $client): array
    {
        return array_filter($this->data['stacks'], static fn (Stack $stack) => $stack->getClient() == $client && null == $stack->getParent());
    }

    /**
     * Count all messages for a client.
     */
    public function countClientMessages(string $client): int
    {
        return array_sum(array_map(fn (Stack $stack) => $this->countStackMessages($stack), $this->getClientRootStacks($client)));
    }

    /**
     * Recursively count message in stack.
     */
    private function countStackMessages(Stack $stack): int
    {
        return 1 + array_sum(array_map(fn (Stack $child) => $this->countStackMessages($child), $this->getChildrenStacks($stack)));
    }

    public function getTotalDuration(): int
    {
        return array_reduce($this->data['stacks'], static fn ($carry, Stack $stack) => $carry + $stack->getDuration(), 0);
    }

    public function collect(Request $request, Response $response, $exception = null): void
    {
        // We do not need to collect any data from the Symfony Request and Response
    }
}
