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
 * @final
 */
class Collector extends DataCollector
{
    /**
     * @var Stack|null
     */
    private $activeStack;

    /**
     * @var int|null
     */
    private $capturedBodyLength;

    public function __construct(?int $capturedBodyLength = null)
    {
        $this->reset();
        $this->capturedBodyLength = $capturedBodyLength;
    }

    /**
     * {@inheritdoc}
     */
    public function reset(): void
    {
        $this->data['stacks'] = [];
        $this->activeStack = null;
    }

    /**
     * {@inheritdoc}
     */
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
    public function activateStack(Stack $stack)
    {
        if (null !== $this->activeStack) {
            $stack->setParent($this->activeStack);
        }

        $this->activeStack = $stack;
    }

    /**
     * Mark the stack as inactive.
     */
    public function deactivateStack(Stack $stack)
    {
        $this->activeStack = $stack->getParent();
    }

    /**
     * @return Stack|null
     */
    public function getActiveStack()
    {
        return $this->activeStack;
    }

    public function addStack(Stack $stack)
    {
        $this->data['stacks'][] = $stack;
    }

    /**
     * @return Stack[]
     */
    public function getChildrenStacks(Stack $parent)
    {
        return array_filter($this->data['stacks'], function (Stack $stack) use ($parent) {
            return $stack->getParent() === $parent;
        });
    }

    /**
     * @return Stack[]
     */
    public function getStacks()
    {
        return $this->data['stacks'];
    }

    /**
     * @return Stack[]
     */
    public function getSuccessfulStacks()
    {
        return array_filter($this->data['stacks'], function (Stack $stack) {
            return !$stack->isFailed();
        });
    }

    /**
     * @return Stack[]
     */
    public function getFailedStacks()
    {
        return array_filter($this->data['stacks'], function (Stack $stack) {
            return $stack->isFailed();
        });
    }

    /**
     * @return array
     */
    public function getClients()
    {
        $stacks = array_filter($this->data['stacks'], function (Stack $stack) {
            return null === $stack->getParent();
        });

        return array_unique(array_map(function (Stack $stack) {
            return $stack->getClient();
        }, $stacks));
    }

    /**
     * @param $client
     *
     * @return Stack[]
     */
    public function getClientRootStacks($client)
    {
        return array_filter($this->data['stacks'], function (Stack $stack) use ($client) {
            return $stack->getClient() == $client && null == $stack->getParent();
        });
    }

    /**
     * Count all messages for a client.
     *
     * @param $client
     *
     * @return int
     */
    public function countClientMessages($client)
    {
        return array_sum(array_map(function (Stack $stack) {
            return $this->countStackMessages($stack);
        }, $this->getClientRootStacks($client)));
    }

    /**
     * Recursively count message in stack.
     *
     * @return int
     */
    private function countStackMessages(Stack $stack)
    {
        return 1 + array_sum(array_map(function (Stack $child) {
            return $this->countStackMessages($child);
        }, $this->getChildrenStacks($stack)));
    }

    /**
     * @return int
     */
    public function getTotalDuration()
    {
        return array_reduce($this->data['stacks'], function ($carry, Stack $stack) {
            return $carry + $stack->getDuration();
        }, 0);
    }

    /**
     * {@inheritdoc}
     */
    public function collect(Request $request, Response $response, $exception = null): void
    {
        // We do not need to collect any data from the Symfony Request and Response
    }
}
