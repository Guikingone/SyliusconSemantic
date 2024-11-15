<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Twig\Components;

use App\Entity\Post;
use Doctrine\ORM\EntityManagerInterface;
use Meilisearch\Bundle\SearchService;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * Live component to display instant search for Posts.
 *
 * See https://symfony.com/bundles/ux-live-component/current/index.html
 *
 * @author Romain Monteil <monteil.romain@gmail.com>
 */
#[AsLiveComponent(name: 'blog_search')]
final class BlogSearchComponent
{
    use DefaultActionTrait;

    /**
     * Properties marked as LiveProp are stateful properties.
     * This means that each time the component is re-rendered, it will remember the original value of the property
     * and set it to the component object.
     *
     * By default, LiveProp are readonly. Making them writable allow users to change their value.
     *
     * See https://symfony.com/bundles/ux-live-component/current/index.html#liveprops-stateful-component-properties
     */
    #[LiveProp(writable: true)]
    public string $query = '';

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly SearchService $searchService
    ) {
    }

    /**
     * @return array<Post>
     */
    public function getPosts(): array
    {
        return '' === $this->query
            ? []
            : $this->searchService->search($this->entityManager, Post::class, $this->query, [
                'hybrid' => [
                    'semanticRatio' => 0.9,
                    'embedder' => 'ollama_mini-lm',
                ]
            ]);
    }
}
